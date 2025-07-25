<?php
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

global $global, $config;

if (!isset($global['systemRootPath'])) {
    require_once '../videos/configuration.php';
}

if (empty($advancedCustomUser)) {
    $advancedCustomUser = AVideoPlugin::getObjectData("CustomizeUser");
}
if (!empty($advancedCustomUser->disableNativeSignUp) && !User::isAdmin()) {
    forbiddenPage("Sign Up Disabled");
}

allowOrigin();
require_once $global['systemRootPath'] . 'objects/user.php';

// Getting the mobile submitted value
$inputJSON = url_get_contents('php://input');
$input = _json_decode($inputJSON, true); //convert JSON into array
if (!empty($input)) {
    foreach ($input as $key => $value) {
        $_POST[$key] = $value;
    }
}
$obj = new stdClass();
$obj->error = true;
$obj->msg = '';
if (empty($ignoreCaptcha)) {
    if (empty($_POST['captcha'])) {
        $obj->error = __("The captcha is empty");
        die(json_encode($obj));
    }
    require_once $global['systemRootPath'] . 'objects/captcha.php';
    $valid = Captcha::validation($_POST['captcha']);
    if (!$valid) {
        $obj->error = __("The captcha is wrong");
        die(json_encode($obj));
    }
}
// check if user already exists
$userCheck = new User(0, $_POST['user'], false);

if (!empty($userCheck->getBdId())) {
    $obj->msg = __("User already exists");
    die(json_encode($obj));
}

if (!empty($advancedCustomUser->forceLoginToBeTheEmail)) {
    $_POST['email'] = $_POST['user'];
}
$_POST['email'] = trim(@$_POST['email']);
if (!empty($advancedCustomUser->emailMustBeUnique)) {
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $obj->error = __("You must specify a valid email")." {$_POST['email']} (create)";
        die(json_encode($obj));
    }
    $userFromEmail = User::getUserFromEmail($_POST['email']);
    if (!empty($userFromEmail)) {
        $obj->error = __("Email already exists");
        die(json_encode($obj));
    }
}

if(empty($_POST['pass']) && !empty($_POST['inputPassword'])){
    $_POST['pass'] = $_POST['inputPassword'];
}

if (empty($_POST['user']) || empty($_POST['pass']) || empty($_POST['email']) || empty($_POST['name'])) {
    $obj->error = __("You must fill all fields");
    die(json_encode($obj));
}

if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $obj->error = __("Invalid Email");
    die(json_encode($obj));
}

$user = new User(0);
$user->setUser($_POST['user']);
$user->setPassword($_POST['pass']);
$user->setEmail($_POST['email']);
$user->setName($_POST['name']);
$user->setPhone(@$_POST['phone']);
$user->setIs_company($_POST['is_company']);

$user->setCanUpload($config->getAuthCanUploadVideos());

$users_id = $user->save();

if (!empty($users_id)) {
    $cu = AVideoPlugin::loadPluginIfEnabled('CustomizeUser');
    if (!empty($cu)) {
        CustomizeUser::setCanShareVideosFromUser($users_id, true);
    }
    if (!empty($advancedCustomUser->userDefaultUserGroup->value)) { // for new users use the default usergroup
        UserGroups::updateUserGroups($users_id, [$advancedCustomUser->userDefaultUserGroup->value], true);
    }
    AVideoPlugin::onUserSignup($users_id);
    $obj->status = $users_id;
    $obj->error = false;
    $obj->msg = __("Your user account has been created!");
    if(!empty($advancedCustomUser->unverifiedEmailsCanNOTLogin)){
        $obj->msg .= '<br>'.__("Sign in to your email to verify your account!");
    }
    if (!empty($_POST['usersExtraInfo'])) {
        User::saveExtraInfo(json_encode($_POST['usersExtraInfo']), $users_id);
    }
}

die(json_encode($obj));
