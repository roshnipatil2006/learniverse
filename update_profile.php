<?php
include 'db.php';

$id = $_POST['id'];
$email = $_POST['email'];
$nickname = $_POST['nickname'];
$avatar = $_POST['avatar']; // URL or image name

$sql = "UPDATE users SET email = ?, nickname = ?, avatar = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $email, $nickname, $avatar, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Profile update failed"]);
}
?>
