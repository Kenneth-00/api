<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Type: application/json");

    error_reporting(E_ERROR);
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') :
        http_response_code(405);
        echo json_encode([
            'success' => 0,
            'message' => 'Bad Reqeust Detected! Only get method is allowed',
        ]);
        exit;
    endif;

    require 'db_connect.php';
    $database = new Operations();
    $conn = $database->dbConnection();

    $id = null;

    if (isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, [
            'options' => [
                'default' => 'all_records',
                'min_range' => 1
            ]
        ]);
        //echo json_encode($id);
    }

    try {

        $sql = is_numeric($id) ? "SELECT * FROM `user_tbl` WHERE user_id='$id'" : "SELECT * FROM `user_tbl`";
        

        $stmt = $conn->prepare($sql);

        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {

            $data = null;
            if (is_numeric($id)) {
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($data);
            } else {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            echo json_encode(
                //'success'=> 1,
                // 'data' => $data,
                $data
            );

        } else {
            echo json_encode([
                'success' => 0,
                'message' => 'No Record Found!',
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => 0,
            'message' => $e->getMessage()
        ]);
        exit;
    }
?>