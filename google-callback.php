<?php
require_once "config/google.php";
require_once "config/database.php";

session_start();

if (isset($_GET['code'])) {

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    $oauth = new Google_Service_Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    $email = $userInfo->email;
    $name = $userInfo->name;
    $picture = $userInfo->picture;

    // check if user exists
    $stmt = mysqli_prepare($conn, "SELECT id, role FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) == 0){

        // create user if not exists
        $role = "customer";

        $insert = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, '', ?)");
        mysqli_stmt_bind_param($insert, "sss", $name, $email, $role);
        mysqli_stmt_execute($insert);

        $user_id = mysqli_insert_id($conn);

    } else {
        $user = mysqli_fetch_assoc($result);
        $user_id = $user['id'];
        $role = $user['role'];
    }

    // create session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['picture'] = $picture;

    // redirect to app
    if ($role === "host") {
        header("Location: host-dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();

} else {
    echo "No authentication code received.";
}
?>