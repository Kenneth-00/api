<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Type: application/json");

    error_reporting(E_ERROR);
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => 0,
            'message' => 'Bad Request Detected! Only GET method is allowed',
        ]);
        exit;
    }

    require 'db_connect.php';
    $database = new Operations();
    $conn = $database->dbConnection();

    $particular_id = null;
    $user_id = null;

    if (isset($_GET['particular_id'])) {
        $particular_id = filter_var($_GET['particular_id'], FILTER_VALIDATE_INT, [
            'options' => [
                'default' => 'all_records',
                'min_range' => 1
            ]
        ]);
    }

    if (isset($_GET['id'])) {
        $user_id = $_GET['id'];
    }

    try {

        if (isset($user_id)){

            $userRoleQuery = "SELECT user_role FROM `user_tbl` WHERE user_id = :user_id";
            $userRoleStmt = $conn->prepare($userRoleQuery);
            $userRoleStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $userRoleStmt->execute();

            $userRoleResult = $userRoleStmt->fetch(PDO::FETCH_ASSOC);
            $userRole = $userRoleResult['user_role'];

            if ($userRole === 'Admin') {

                if (isset($particular_id) && isset($user_id)) {
                    $sql = "SELECT u.name, a.count
                    FROM `actualreportbytotal_tbl` a
                    INNER JOIN `user_tbl` u ON u.user_id = a.user_id
                    WHERE a.particular_id = :particular_id";

                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(':particular_id', $particular_id, PDO::PARAM_INT);
                    $stmt->execute();

                    $fetchdata = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $sql = "SELECT u.name, a.count
                    FROM `actualreportbytotal_tbl` a
                    INNER JOIN `user_tbl` u ON u.user_id = a.user_id
                    WHERE a.particular_id = :particular_id";
            
                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(':particular_id', $particular_id, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    $fetchdata = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $combinedData = [];
                    
                    if ($particular_id == 4) {
                        // Remove duplicates and fetch a single count value for each name
                        $uniqueNames = [];
                        
                        foreach ($fetchdata as $item) {
                            $name = $item['name'];
                            $count = intval($item['count']);
                            
                            if (!in_array($name, $uniqueNames)) {
                                $uniqueNames[] = $name;
                                $combinedData[] = [
                                    "name" => $name,
                                    "count" => $count
                                ];
                            }
                        }
                    } else {
                        // Perform the computation as before
                        $combinedCounts = [];
                    
                        foreach ($fetchdata as $item) {
                            $name = $item['name'];
                            $count = intval($item['count']);
                            
                            if (!isset($combinedCounts[$name])) {
                                $combinedCounts[$name] = 0;
                            }
                            
                            $combinedCounts[$name] += $count;
                        }
                    
                        foreach ($combinedCounts as $name => $count) {
                            $combinedData[] = [
                                "name" => $name,
                                "count" => $count
                            ];
                        }
                    }
                    
                    echo json_encode($combinedData);
                    
                } else {
                    $sql = "SELECT p.particulars_id, p.particulars, a.quarter_id, u.name, a.count
                    FROM user_tbl u
                    INNER JOIN actualreportbytotal_tbl a ON u.user_id = a.user_id
                    INNER JOIN particulars_tbl p ON a.particular_id = p.particulars_id
                    ORDER BY p.particulars_id = a.particular_id";

                    $stmt = $conn->prepare($sql);
                    $stmt->execute();

                    $fetchdata = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $rows = array();

                    // Group the fetched data by "particulars" column
                    foreach ($fetchdata as $row) {
                        $particulars = $row['particulars'];
                        $particular_id = $row['particulars_id'];
                        $quarter = $row['quarter_id'];
                    
                        if (!isset($rows[$particulars][$quarter])) {
                            $rows[$particulars][$quarter] = array();
                        }
                        
                        if (!isset($rows[$quarter])) {
                            $rows[$quarter] = array();
                        }
                    
                        $rows[$particulars][$quarter][] = $row;
                    }
                    
                    echo json_encode($rows);
                    
                }
                
            } else {
                $sql = "SELECT u.name, a.count
                        FROM `actualreportbytotal_tbl` a
                        INNER JOIN `user_tbl` u ON u.user_id = a.user_id
                        WHERE a.particular_id = :particular_id AND u.user_id = :user_id";

                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':particular_id', $particular_id, PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();

                $fetchdata = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode($fetchdata);
            }
        
        } else {

            $sql = "SELECT * FROM `actualreportbytotal_tbl`";
            $stmt = $conn -> prepare($sql);
            $stmt -> execute();
            $fetchdata = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($fetchdata);
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
