<?php

class MOAMysql {

    private $servername = "127.0.0.1";
    private $username = "root";
    private $password = "123456";
    private $dbname = "moadb";

    public function connect() {
        // Create connection
        $conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        } 
        return $conn;
    }

    //写入新的信息到数据库
    public function WriteToDB($userId, $visibility, $receive_uids, $noticebody) {
        //设置时区为东八区
        date_default_timezone_set('PRC');
        $conn  = $this->connect();
        $timestamp = date('Y-m-d H:i:s');
        echo $timestamp;
        $sql = "insert into MOA_OAMessage (uid, visibility, timestamp, receive_uids, body) value (".$userId.", ".$visibility.", '".$timestamp."', '".$receive_uids."' , '".$noticebody."');";
        $result = $conn->query($sql);
        if(!$result) {
            echo "ERROR : ". $conn->error;
        }
        if ($conn->affected_rows > 0) {
            return true;
        }
        return false;
    }

    //写入当前信息已读信号
    public function WriteReadToDB($mid) {
        $conn = $this->connect();
        $sql = "update MOA_OAMessage set isread = 1 where mid = ".$mid.";";
        $result = $conn->query($sql);
        if(!$result) {
            echo "ERROR : ". $conn->error;
        }
        if ($conn->affected_rows > 0) {
            return true;
        }
        return false;
    }

    //取出未读的私信
    public function getUnreadMessageFromDB($userId) {
        $conn = $this->connect();
        $sql = "select * from MOA_OAMessage where isread = 0 and receive_uids like '%".$userId."%';";
        $result = $conn->query($sql);
        if(!$result) {
            echo "ERROR : ". $conn->error;
        }
        $data = array();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_object()) {
                array_push($data, $row);
            }
        }

        $conn->close();
        return $data;
    }

    //得到未读的通知
    public function getUnreadNoticeFromDB($userId) {
        $conn = $this->connect();

        //取出组号
        $sql = "select `group` from MOA_Worker where uid = ".$userId.";";
        $result = $conn->query($sql);
        if(!$result) {
            echo "ERROR : ". $conn->error;
        }
        if ($result->num_rows > 0) {
            $group = $result->fetch_object();
            $group = $group->group;
        }
        else return;
        
        //取出未读广播
        $sql = "select * from MOA_OAMessage where isread = 0 and  `visibility`  = ".$group.";";
        $result = $conn->query($sql);
        if(!$result) {
            echo "ERROR : ". $conn->error;
        }
        $data = array();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_object()) {
                array_push($data, $row);
            }
        }

        $conn->close();
        return $data;
    }

    //获取当前私信的聊天记录
    public function getChatHistoryFromDB($userId, $receiveUser) {
        $conn = $this->connect();
        //由私信人发送的聊天记录
        $sql = "select * from MOA_OAMessage where visibility = 0 and receive_uids like '%".$receiveUser."%'";
        $result = $conn->query($sql);
        if(!$result) {
            echo "ERROR : ". $conn->error;
        }
        $data = array();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_object()) {
                array_push($data, $row);
            }
        }
        
        // 由被私信人发送的聊天记录
        $sql = "select * from MOA_OAMessage where visibility = 0 and uid = ".$receiveUser." and receive_uids like '%".$userId."%';";
        $result = $conn->query($sql);
        if(!$result) {
            echo "ERROR : ". $conn->error;
        }
        if ($result->num_rows > 0) {
            while($row = $result->fetch_object()) {
                array_push($data, $row);
            }
        }
        $conn->close();
        return $data;
    }

    // 检查当前是否有未读的私信信息
    public function getNewChatMessageFromDB($userId, $receiveUser) {
        $conn = $this->connect();
        $sql = "select * from MOA_OAMessage where visibility = 0 and isread = 0 and uid = ".$receiveUser." and receive_uids like '%".$userId."%'";
        $result = $conn->query($sql);
        if(!$result) {
            echo "ERROR : ". $conn->error;
        }
        $data = array();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_object()) {
                array_push($data, $row);
            }
        }

        $conn->close();
        return $data; 
    }

}