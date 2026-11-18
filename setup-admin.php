<?php
require_once __DIR__.'/includes/bootstrap.php';
$count=(int)db()->query("SELECT COUNT(*) FROM administrators")->fetchColumn();
$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    if($count>0){$message='An administrator already exists. Remove this setup file after installation.';}
    else{
        $username=trim($_POST['username']??'');$name=trim($_POST['full_name']??'');$password=$_POST['password']??'';
        if(strlen($username)<4||strlen($name)<2||strlen($password)<10){$message='Use a username of at least 4 characters, a name and a password of at least 10 characters.';}
        else{$s=db()->prepare("INSERT INTO administrators(username,full_name,password_hash) VALUES(?,?,?)");$s->execute([$username,$name,password_hash($password,PASSWORD_DEFAULT)]);$message='Administrator created. Delete setup-admin.php now, then sign in.';$count=1;}
    }
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Initial Admin Setup</title><link rel="stylesheet" href="assets/css/style.css"><meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf']) ?>"></head><body class="login-page"><form method="post" class="login-card"><h1>Initial <em>setup</em></h1><p>Create the first GreenLoop administrator. This page is disabled once an admin exists.</p><?php if($count===0): ?><label>Full name<input name="full_name" required></label><label>Username<input name="username" required></label><label>Password (10+ characters)<input name="password" type="password" minlength="10" required></label><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>"><button class="btn btn-primary submit-btn">Create administrator</button><?php else: ?><p>An administrator account already exists.</p><?php endif; ?><p><?= htmlspecialchars($message) ?></p><a class="back-link" href="admin-login.php">Go to administrator login</a></form><script>document.querySelector('form')?.addEventListener('submit',e=>{const h=document.createElement('input');h.type='hidden';h.name='csrf';h.value=document.querySelector('meta[name=csrf-token]').content;e.currentTarget.append(h);});</script></body></html>
