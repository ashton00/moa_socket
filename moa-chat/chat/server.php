<?php
use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Autoloader;
use PHPSocketIO\SocketIO;

// composer autoload
include __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../../src/autoload.php';
require_once('./MysqlOperate.php');

$io = new SocketIO(2020);
$MysqlOperate = new MOAMysql();

$io->on('connection', function($socket){
    $socket->addedUser = false;
    //notice 
    //---------------------------------------------------------
    $socket->on('createnotice', function($data) use($socket) {
        global $MysqlOperate;
        $userId = $data['userId'];
        $noticebody = $data['noticebody'];
        $visibility = $data['visibility'];
        $receive_uids = $data['receive_uids'];

        echo "\nOn: create notice\n";
        echo "----\$userId: ".$userId."\n";
        echo "----\$noticebody: ".$noticebody."\n";
        echo "----\$visibility: ".$visibility."\n";
        echo "----\$receive_uids: ".$receive_uids."\n";

        if(!$MysqlOperate->WriteToDB($userId, $visibility, $receive_uids, $noticebody)) {
            echo "----操作: WriteToDB\n";
            echo "----写入数据库失败: " . $noticebody;
        }

        //触发用户去查看当前是否有未读消息
        echo "\nEmit: check new notice\n";
        $socket->broadcast->emit('checknewnotice');
    });
    
    $socket->on('checkunread', function($data) use($socket) {
        global $MysqlOperate;
        //检查当前用户是否已经有未读的notice
        $userId = $data['userId'];
        echo "\$userId: " . $userId;
        $noticelist = $MysqlOperate->getUnreadNoticeFromDB($userId);
        $messagelist = $MysqlOperate->getUnreadMessageFromDB($userId);
        echo "\nOn: check unread\n";
        echo "----\$userId: ".$userId."\n";
        echo "----\$noticelist: ";
        print_r($noticelist);
        echo "----\$messagelist: ";
        print_r($messagelist);
        echo "\n";
        
        if(count($noticelist) == 0) {
            //do nothing
        }

        if((count($noticelist) != 0) || (count($messagelist) != 0)) {
            echo "\nEmit: new notice\n";
            $socket->emit('newnotice', array(
                //notice content
                'noticelist' => $noticelist,
                'messagelist' => $messagelist,
                'user' => $userId
            ));
        } 
    });

    $socket->on('alreadyread', function($data) use($socket) {
        global $MysqlOperate;
        $userId = $data['userId'];
        $mid = $data['mid'];

        echo "\nOn: already read\n";
        echo "----\$userId: ".$userId."\n";
        echo "----\$mid: ".$mid."\n";

        if(!$MysqlOperate->WriteReadToDB($mid)) {
            echo "----操作: already read -> WriteReadToDB";
            echo "----写入数据库失败，mid:" . $mid;
        }
    });
    //---------------------------------------------------------
    

    //chat 
    //---------------------------------------------------------
    $socket->on('newchat', function ($data) use($socket) {
        global $MysqlOperate;
        //userId为发起者，receiveId为私信对象
        $userId = $data['userId'];
        $receiveUser = $data['receiveUser'];
        $history = $MysqlOperate->getChatHistoryFromDB($userId, $receiveUser);

        echo "\nOn: new chat";
        echo "----\$userId: ".$userId."\n";
        echo "----\$receiveId: ".$receiveId."\n";
        echo "----\$history: ";
        print_r($history);
        echo "\n";

        echo "\nEmit: init chat\n";
        $socket->emit('initchat', array(
            'history' => $history
        ));
    });

    // $socket->on('new message', function($data) use($socket) {
    //     $userId = $data->userId;
    //     $noticebody = $data->noticebody;
    //     $visibility = $data->visibility;
    //     $receive_uids = $data->receive_uids
    //     if(!$MysqlOperate->WriteToDB($userId, $visibility, $receive_uids, $noticebody)) {
    //         echo "操作: new message -> WriteToDB";
    //         echo "写入数据库失败: " . $noticebody;
    //     }
    // });

    $socket->on('checkmessage', function($data) use($socket) {
        global $MysqlOperate;
        $userId = $data['userId'];
        $receiveUser = $data['receiveUser'];
        $msglist = $MysqlOperate->getNewChatMessageFromDB($userId, $receiveUser);

        echo "\nOn: check message";
        echo "----\$userId: ".$userId."\n";
        echo "----\$receiveUser: ".$receiveUser."\n";
        echo "----\$msglist: ";
        print_r($msglist);
        echo "\n";

        echo "\nEmit: new message\n";
        if(count(msg) != 0) {
            foreach($msglist as $msg) {
                $socket->emit('newmessage', array(
                    'msg' => $msg
                ));
            }
        }
    });
    //---------------------------------------------------------
   
});

$web = new WebServer('http://127.0.0.1:2022');
$web->addRoot('localhost', __DIR__ . '/public');

Worker::runAll();

// 主要事件，都围绕是创建一个通知便条，或者是创建一个聊天窗口来进行

// on
// ----create notice
// --------通知，创建一个通知（私信或者广播）
//         $userId
//         $noticebody
//         $visibility
//         $receive_uids

// ----check unread
// --------通知，检查未读通知
//          $userId

// ----already read
// --------通知，写入通知已读标记
//         $userId
//         $mid

// ----new chat
// --------聊天窗口：获取对话历史纪录
//         $userId
//         $receiveUser

// ----check message
// --------聊天窗口：检查新对话
//         $userId
//         $receiveUser

// emit 
// ----check new notice
// --------全体：通知，创建完通知后，广播全体，检查当前是否有新消息

// ----new notice
// --------个人：通知，当检查当前有未读通知是，广播个人未读通知
//         'notice' => $noticelist
//         'user' => $userId;

// ----init chat
// --------个人：聊天窗口，发回个人聊天记录
//         'history' => $history;

// ----new message
// --------个人：聊天窗口，发回新消息
//         'msg'->msg;
