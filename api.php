<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'csrf' && $method === 'GET') {
    json_response(['success' => true, 'csrf' => $_SESSION['csrf']]);
}

try {
    if ($method === 'POST') check_csrf();

    if ($action === 'create_request' && $method === 'POST') {
        $name = clean_string($_POST['name'] ?? '', 120);
        $phone = clean_string($_POST['phone'] ?? '', 30);
        $region = clean_string($_POST['region'] ?? '', 80);
        $address = clean_string($_POST['address'] ?? '', 255);
        $category = clean_string($_POST['category'] ?? '', 60);
        $quantity = clean_string($_POST['quantity'] ?? '', 80);
        $date = $_POST['preferred_date'] ?? '';
        $time = clean_string($_POST['preferred_time'] ?? '', 40);
        $latValue = $_POST['latitude'] ?? '';
        $lngValue = $_POST['longitude'] ?? '';
        $lat = is_scalar($latValue) ? filter_var((string)$latValue, FILTER_VALIDATE_FLOAT) : false;
        $lng = is_scalar($lngValue) ? filter_var((string)$lngValue, FILTER_VALIDATE_FLOAT) : false;
        $notes = clean_string($_POST['notes'] ?? '', 1000);
        $allowed = ['Plastic','Paper','Organic waste','Metal','Glass','Electronic waste','General waste'];
        if (!$name || !$phone || !$region || !in_array($category, $allowed, true)) {
            json_response(['success'=>false,'message'=>'Please provide your name, phone, region and waste category.'], 422);
        }
        if ($lat === false || $lat === null || $lng === false || $lng === null ||
            $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            json_response(['success'=>false,'message'=>'Choose a pickup location on the map or add your current GPS location.'], 422);
        }
        if (!$address) $address = sprintf('Pinned map location (%.6f, %.6f)', $lat, $lng);
        if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            json_response(['success'=>false,'message'=>'Please provide a valid preferred date.'], 422);
        }
        $ref = 'WW-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $stmt = db()->prepare("INSERT INTO collection_requests
            (reference_code, customer_name, phone, region, address, waste_category, estimated_quantity,
             preferred_date, preferred_time, customer_latitude, customer_longitude, notes, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'Pending')");
        $stmt->execute([$ref,$name,$phone,$region,$address,$category,$quantity ?: null,
            $date ?: null,$time ?: null,$lat,$lng,$notes ?: null]);
        json_response(['success'=>true,'message'=>'Your collection request has been received.','reference'=>$ref]);
    }

    if ($action === 'track' && $method === 'GET') {
        $ref = clean_string($_GET['reference'] ?? '', 40);
        $phone = clean_string($_GET['phone'] ?? '', 30);
        if (!$ref || !$phone) json_response(['success'=>false,'message'=>'Enter your reference number and phone number.'],422);
        $q = db()->prepare("SELECT id, reference_code, customer_name, region, address, waste_category, preferred_date,
            preferred_time, status, assigned_vehicle_id FROM collection_requests WHERE reference_code=? AND phone=? LIMIT 1");
        $q->execute([$ref,$phone]); $request = $q->fetch();
        if (!$request) json_response(['success'=>false,'message'=>'No matching collection was found. Check your details.'],404);
        $vehicle = null;
        $location = null;
        if ($request['assigned_vehicle_id']) {
            $v = db()->prepare("SELECT registration_number, vehicle_type FROM vehicles WHERE id=?");
            $v->execute([$request['assigned_vehicle_id']]); $vehicle = $v->fetch();
            $l = db()->prepare("SELECT latitude, longitude, accuracy, recorded_at FROM vehicle_locations
                WHERE vehicle_id=? ORDER BY recorded_at DESC LIMIT 1");
            $l->execute([$request['assigned_vehicle_id']]); $location = $l->fetch();
        }
        json_response(['success'=>true,'request'=>$request,'vehicle'=>$vehicle,'location'=>$location]);
    }

    if ($action === 'report' && $method === 'POST') {
        $name=clean_string($_POST['name']??'',120); $phone=clean_string($_POST['phone']??'',30);
        $location=clean_string($_POST['location']??'',255); $category=clean_string($_POST['category']??'',60);
        $description=clean_string($_POST['description']??'',1500);
        if (!$name || !$phone || !$location || !$category || !$description) json_response(['success'=>false,'message'=>'Complete all required fields.'],422);
        $imagePath = null;
        if (!empty($_FILES['evidence']['name'])) {
            $f=$_FILES['evidence'];
            if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5*1024*1024) json_response(['success'=>false,'message'=>'Image must be under 5MB.'],422);
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!isset($types[$mime])) json_response(['success'=>false,'message'=>'Upload a JPG, PNG or WEBP image.'],422);
            $dir=__DIR__.'/uploads/reports'; if(!is_dir($dir)) mkdir($dir,0755,true);
            $file=bin2hex(random_bytes(12)).'.'.$types[$mime];
            if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$file)) json_response(['success'=>false,'message'=>'Could not save image.'],500);
            $imagePath='uploads/reports/'.$file;
        }
        $ref='WR-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));
        $s=db()->prepare("INSERT INTO environmental_reports(reference_code,reporter_name,phone,location,category,description,evidence_path) VALUES(?,?,?,?,?,?,?)");
        $s->execute([$ref,$name,$phone,$location,$category,$description,$imagePath]);
        json_response(['success'=>true,'message'=>'Thank you. Your environmental report has been submitted.','reference'=>$ref]);
    }

    if ($action === 'create_listing' && $method === 'POST') {
        $name=clean_string($_POST['name']??'',120); $phone=clean_string($_POST['phone']??'',30);
        $material=clean_string($_POST['material']??'',60); $qty=filter_var($_POST['quantity']??null,FILTER_VALIDATE_FLOAT);
        $unit=clean_string($_POST['unit']??'kg',20); $location=clean_string($_POST['location']??'',180);
        $description=clean_string($_POST['description']??'',1000);
        if(!$name||!$phone||!$material||!$qty||$qty<=0||!$location) json_response(['success'=>false,'message'=>'Enter valid listing details.'],422);
        $s=db()->prepare("INSERT INTO recycling_listings(seller_name,phone,material,quantity,unit,location,description) VALUES(?,?,?,?,?,?,?)");
        $s->execute([$name,$phone,$material,$qty,$unit,$location,$description ?: null]);
        json_response(['success'=>true,'message'=>'Your recyclable material listing has been submitted for review.']);
    }

    if ($action === 'login' && $method === 'POST') {
        $role=clean_string($_POST['role']??'',20);
        $username=clean_string($_POST['username']??'',120);
        $password=(string)($_POST['password']??'');
        if (!in_array($role,['admin','driver'],true)) json_response(['success'=>false,'message'=>'Invalid login type.'],422);
        $table=$role==='admin'?'administrators':'drivers';
        $field=$role==='admin'?'username':'email';
        $s=db()->prepare("SELECT id, {$field} AS login_name, full_name, password_hash, status FROM {$table} WHERE {$field}=? LIMIT 1");
        $s->execute([$username]); $u=$s->fetch();
        if(!$u || !password_verify($password,$u['password_hash']) || $u['status']!=='Active') json_response(['success'=>false,'message'=>'Incorrect login details or inactive account.'],401);
        session_regenerate_id(true);
        $_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['full_name'],'role'=>$role];
        $next = $_POST['next'] ?? '';
        $redirect = $role === 'admin' && $next === 'admin-create-account.php'
            ? 'admin-create-account.php'
            : ($role === 'admin' ? 'admin.php' : 'driver.php');
        json_response(['success'=>true,'redirect'=>$redirect]);
    }

    if ($action === 'logout' && $method === 'POST') {
        $_SESSION=[]; session_destroy(); json_response(['success'=>true]);
    }

    if ($action === 'driver_assignments' && $method === 'GET') {
        require_role('driver');
        $s=db()->prepare("SELECT va.id,cr.id request_id,cr.reference_code,cr.customer_name,cr.phone,cr.region,cr.address,cr.waste_category,cr.preferred_date,cr.preferred_time,cr.status,
            v.id vehicle_id,v.registration_number
            FROM vehicle_assignments va JOIN collection_requests cr ON cr.id=va.collection_request_id
            JOIN vehicles v ON v.id=va.vehicle_id
            WHERE va.driver_id=? AND va.active=1 ORDER BY cr.created_at DESC");
        $s->execute([$_SESSION['user']['id']]); json_response(['success'=>true,'assignments'=>$s->fetchAll()]);
    }

    if ($action === 'driver_start' && $method === 'POST') {
        require_role('driver');
        $assignment=(int)($_POST['assignment_id']??0);
        $s=db()->prepare("SELECT va.id,va.vehicle_id,cr.id request_id FROM vehicle_assignments va
            JOIN collection_requests cr ON cr.id=va.collection_request_id WHERE va.id=? AND va.driver_id=? AND va.active=1");
        $s->execute([$assignment,$_SESSION['user']['id']]); $a=$s->fetch();
        if(!$a) json_response(['success'=>false,'message'=>'Assignment not found.'],404);
        db()->prepare("UPDATE collection_requests SET status='Driver En Route' WHERE id=?")->execute([$a['request_id']]);
        db()->prepare("UPDATE vehicle_assignments SET started_at=NOW() WHERE id=?")->execute([$assignment]);
        json_response(['success'=>true,'vehicle_id'=>(int)$a['vehicle_id']]);
    }

    if ($action === 'location_update' && $method === 'POST') {
        require_role('driver');
        $assignment=(int)($_POST['assignment_id']??0);
        $lat=filter_var($_POST['latitude']??null,FILTER_VALIDATE_FLOAT);
        $lng=filter_var($_POST['longitude']??null,FILTER_VALIDATE_FLOAT);
        $accuracy=filter_var($_POST['accuracy']??null,FILTER_VALIDATE_FLOAT);
        if($lat===false||$lng===false||$lat < -90||$lat>90||$lng < -180||$lng>180) json_response(['success'=>false,'message'=>'Invalid GPS coordinates.'],422);
        $s=db()->prepare("SELECT vehicle_id FROM vehicle_assignments WHERE id=? AND driver_id=? AND active=1");
        $s->execute([$assignment,$_SESSION['user']['id']]); $a=$s->fetch();
        if(!$a) json_response(['success'=>false,'message'=>'No active assignment found.'],403);
        db()->prepare("INSERT INTO vehicle_locations(vehicle_id,driver_id,assignment_id,latitude,longitude,accuracy) VALUES(?,?,?,?,?,?)")
            ->execute([$a['vehicle_id'],$_SESSION['user']['id'],$assignment,$lat,$lng,$accuracy ?: null]);
        db()->prepare("UPDATE vehicles SET status='Active' WHERE id=?")->execute([$a['vehicle_id']]);
        json_response(['success'=>true,'message'=>'Location updated.']);
    }

    if ($action === 'driver_complete' && $method === 'POST') {
        require_role('driver');
        $assignment=(int)($_POST['assignment_id']??0);
        $s=db()->prepare("SELECT va.id,va.vehicle_id,cr.id request_id
            FROM vehicle_assignments va JOIN collection_requests cr ON cr.id=va.collection_request_id
            WHERE va.id=? AND va.driver_id=? AND va.active=1");
        $s->execute([$assignment,$_SESSION['user']['id']]); $a=$s->fetch();
        if(!$a) json_response(['success'=>false,'message'=>'Assignment not found.'],404);
        db()->prepare("UPDATE collection_requests SET status='Completed' WHERE id=?")->execute([$a['request_id']]);
        db()->prepare("UPDATE vehicle_assignments SET active=0,completed_at=NOW() WHERE id=?")->execute([$assignment]);
        db()->prepare("UPDATE vehicles SET status='Available' WHERE id=?")->execute([$a['vehicle_id']]);
        json_response(['success'=>true,'message'=>'Pickup completed.']);
    }

    if ($action === 'admin_drivers' && $method === 'GET') {
        require_role('admin');
        $drivers = db()->query("SELECT d.id,d.full_name,d.email,d.phone,d.licence_number,d.status,d.created_at,
            (SELECT COUNT(*) FROM vehicle_assignments va WHERE va.driver_id=d.id AND va.active=1) active_assignments
            FROM drivers d ORDER BY d.created_at DESC")->fetchAll();
        json_response(['success'=>true,'drivers'=>$drivers]);
    }

    if ($action === 'admin_create_admin' && $method === 'POST') {
        require_role('admin');
        $name = clean_string($_POST['full_name'] ?? '', 120);
        $username = clean_string($_POST['username'] ?? '', 80);
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        if (strlen($name) < 2 || strlen($username) < 4 || strlen($password) < 10) {
            json_response(['success'=>false,'message'=>'Enter a name, a username of at least 4 characters and a password of at least 10 characters.'],422);
        }
        if ($password !== $confirmPassword) {
            json_response(['success'=>false,'message'=>'The passwords do not match.'],422);
        }
        try {
            $stmt = db()->prepare("INSERT INTO administrators (username,full_name,password_hash) VALUES (?,?,?)");
            $stmt->execute([$username,$name,password_hash($password,PASSWORD_DEFAULT)]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') json_response(['success'=>false,'message'=>'That username is already in use.'],409);
            throw $e;
        }
        json_response(['success'=>true,'message'=>'Admin account created successfully.']);
    }

    if ($action === 'admin_create_driver' && $method === 'POST') {
        require_role('admin');
        $name = clean_string($_POST['full_name'] ?? '', 120);
        $email = strtolower(clean_string($_POST['email'] ?? '', 160));
        $phone = clean_string($_POST['phone'] ?? '', 30);
        $licence = clean_string($_POST['licence_number'] ?? '', 80);
        $password = (string)($_POST['password'] ?? '');
        if (!$name || !$phone || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            json_response(['success'=>false,'message'=>'Enter a name, valid email, phone number and password of at least 8 characters.'],422);
        }
        try {
            $stmt = db()->prepare("INSERT INTO drivers (full_name,email,phone,licence_number,password_hash,status) VALUES (?,?,?,?,?,'Active')");
            $stmt->execute([$name,$email,$phone,$licence ?: null,password_hash($password,PASSWORD_DEFAULT)]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') json_response(['success'=>false,'message'=>'A driver with that email address already exists.'],409);
            throw $e;
        }
        json_response(['success'=>true,'message'=>'Driver account created successfully. The driver can now sign in using their email and password.','driver_id'=>(int)db()->lastInsertId()]);
    }

    if ($action === 'admin_toggle_driver' && $method === 'POST') {
        require_role('admin');
        $driver = (int)($_POST['driver_id'] ?? 0);
        $stmt = db()->prepare("SELECT id,status FROM drivers WHERE id=? LIMIT 1");
        $stmt->execute([$driver]);
        $record = $stmt->fetch();
        if (!$record) json_response(['success'=>false,'message'=>'Driver account not found.'],404);
        $active = db()->prepare("SELECT COUNT(*) FROM vehicle_assignments WHERE driver_id=? AND active=1");
        $active->execute([$driver]);
        if ((int)$active->fetchColumn() > 0) json_response(['success'=>false,'message'=>'This driver has an active collection assignment. Complete or reassign it before deactivating the account.'],409);
        $newStatus = $record['status'] === 'Active' ? 'Inactive' : 'Active';
        db()->prepare("UPDATE drivers SET status=? WHERE id=?")->execute([$newStatus,$driver]);
        json_response(['success'=>true,'message'=>'Driver account is now '.$newStatus.'.','status'=>$newStatus]);
    }


    if ($action === 'admin_vehicles' && $method === 'GET') {
        require_role('admin');
        $vehicles = db()->query("SELECT v.id,v.registration_number,v.vehicle_type,v.vehicle_model,v.capacity,v.status,
            d.full_name AS driver_name
            FROM vehicles v LEFT JOIN drivers d ON d.id=v.assigned_driver_id
            ORDER BY v.created_at DESC")->fetchAll();
        json_response(['success'=>true,'vehicles'=>$vehicles]);
    }

    if ($action === 'admin_create_vehicle' && $method === 'POST') {
        require_role('admin');
        $registration = strtoupper(clean_string($_POST['registration_number'] ?? '', 40));
        $type = clean_string($_POST['vehicle_type'] ?? '', 80);
        $model = clean_string($_POST['vehicle_model'] ?? '', 100);
        $capacity = clean_string($_POST['capacity'] ?? '', 60);
        if ($registration === '' || $type === '') {
            json_response(['success'=>false,'message'=>'Enter the vehicle registration number and vehicle type.'], 422);
        }
        try {
            $stmt = db()->prepare("INSERT INTO vehicles (registration_number,vehicle_type,vehicle_model,capacity,status)
                VALUES (?,?,?,?, 'Available')");
            $stmt->execute([$registration,$type,$model ?: null,$capacity ?: null]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                json_response(['success'=>false,'message'=>'A vehicle with this registration number is already registered.'], 409);
            }
            throw $e;
        }
        json_response(['success'=>true,'message'=>'Vehicle registered successfully. It is now available for assignment.','vehicle_id'=>(int)db()->lastInsertId()]);
    }

    if ($action === 'admin_data' && $method === 'GET') {
        require_role('admin');
        $pdo=db();
        $stats=[
            'requests'=>(int)$pdo->query("SELECT COUNT(*) FROM collection_requests")->fetchColumn(),
            'pending'=>(int)$pdo->query("SELECT COUNT(*) FROM collection_requests WHERE status='Pending'")->fetchColumn(),
            'completed'=>(int)$pdo->query("SELECT COUNT(*) FROM collection_requests WHERE status='Completed'")->fetchColumn(),
            'drivers'=>(int)$pdo->query("SELECT COUNT(*) FROM drivers WHERE status='Active'")->fetchColumn(),
            'vehicles'=>(int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn(),
            'reports'=>(int)$pdo->query("SELECT COUNT(*) FROM environmental_reports WHERE status NOT IN ('Resolved','Rejected')")->fetchColumn()
        ];
        $requests=$pdo->query("SELECT id,reference_code,customer_name,phone,region,waste_category,status,created_at FROM collection_requests ORDER BY created_at DESC LIMIT 12")->fetchAll();
        $vehicles=$pdo->query("SELECT v.id,v.registration_number,v.vehicle_type,v.status,vl.latitude,vl.longitude,vl.recorded_at,d.full_name driver_name
            FROM vehicles v LEFT JOIN drivers d ON d.id=v.assigned_driver_id
            LEFT JOIN vehicle_locations vl ON vl.id=(SELECT MAX(v2.id) FROM vehicle_locations v2 WHERE v2.vehicle_id=v.id)
            WHERE v.status='Active'")->fetchAll();
        json_response(['success'=>true,'stats'=>$stats,'requests'=>$requests,'vehicles'=>$vehicles]);
    }

    if ($action === 'admin_assign' && $method === 'POST') {
        require_role('admin');
        $request=(int)($_POST['request_id']??0); $driver=(int)($_POST['driver_id']??0); $vehicle=(int)($_POST['vehicle_id']??0);
        $pdo=db();
        $check=$pdo->prepare("SELECT id FROM collection_requests WHERE id=?");$check->execute([$request]);
        if(!$check->fetch()) json_response(['success'=>false,'message'=>'Collection request not found.'],404);
        $check=$pdo->prepare("SELECT id FROM drivers WHERE id=? AND status='Active'");$check->execute([$driver]);
        if(!$check->fetch()) json_response(['success'=>false,'message'=>'Choose an active driver.'],422);
        $check=$pdo->prepare("SELECT id FROM vehicles WHERE id=? AND status IN ('Available','Assigned')");$check->execute([$vehicle]);
        if(!$check->fetch()) json_response(['success'=>false,'message'=>'Vehicle is not available.'],422);
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO vehicle_assignments(collection_request_id,vehicle_id,driver_id) VALUES(?,?,?)")->execute([$request,$vehicle,$driver]);
        $pdo->prepare("UPDATE collection_requests SET assigned_vehicle_id=?,status='Assigned' WHERE id=?")->execute([$vehicle,$request]);
        $pdo->prepare("UPDATE vehicles SET assigned_driver_id=?,status='Assigned' WHERE id=?")->execute([$driver,$vehicle]);
        $pdo->commit();
        json_response(['success'=>true,'message'=>'Driver and vehicle assigned successfully.']);
    }

    if ($action === 'admin_options' && $method === 'GET') {
        require_role('admin');
        $drivers=db()->query("SELECT id,full_name FROM drivers WHERE status='Active' ORDER BY full_name")->fetchAll();
        $vehicles=db()->query("SELECT id,registration_number FROM vehicles WHERE status IN ('Available','Assigned') ORDER BY registration_number")->fetchAll();
        $requests=db()->query("SELECT id,reference_code,customer_name FROM collection_requests WHERE status IN ('Pending','Approved') ORDER BY created_at DESC")->fetchAll();
        json_response(['success'=>true,'drivers'=>$drivers,'vehicles'=>$vehicles,'requests'=>$requests]);
    }

    json_response(['success'=>false,'message'=>'Unknown action.'],404);
} catch (Throwable $e) {
    error_log($e->getMessage());
    json_response(['success'=>false,'message'=>'A server error occurred. Check the PHP error log and database connection.'],500);
}
