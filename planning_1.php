<?php 
error_reporting(0);
ini_set('display_errors','on');
//設定Token 
include('./config.php');
$ChannelSecret = $Secret; 
$ChannelAccessToken = $AccessToken;

const URL_IMG='https://line.jowinwin.com/line_Planning/img/';

//讀取資訊 
$HttpRequestBody = file_get_contents('php://input'); 
$HeaderSignature = isset($_SERVER['HTTP_X_LINE_SIGNATURE'])?$_SERVER['HTTP_X_LINE_SIGNATURE']:''; 
//驗證來源是否是LINE官方伺服器 
$Hash = hash_hmac('sha256', $HttpRequestBody, $ChannelSecret, true); 
$HashSignature = base64_encode($Hash); 
file_put_contents('log1.txt', $HttpRequestBody."\n",FILE_APPEND); 
if($HashSignature != $HeaderSignature) 
{ 
    die('hash error!'); 
    exit();
} 
//例外
$pass=['報修格式'];
$onoff_type=true;
$conn=connectDB();
$G7_type= g7Type();
 //解析
$DataBody=json_decode($HttpRequestBody, true);
if(empty($DataBody['events'])){
    echo 'error';
    exit();
} 
//逐一執行事件
foreach($DataBody['events'] as $Event)
{
    $user=new LineUser($ChannelAccessToken,$Event);
    //當bot收到任何訊息
    if($Event['type'] == 'message'){
        foreach($G7_type as $k=>$_type_array){
            foreach($_type_array as $_type){
                if(strpos($Event['message']['text'],$_type) !== false){
                    if(strpos($Event['message']['text'],'常見問題') !== false){
                        common_QA_mid($k);
                        $onoff_type=false;
                        break 2;
                    }
                }
            }
        }
        $ans=ansQA($Event['message']['text']);
        if($ans['0']){
            if(is_array($ans['msg'])){
                $_pass=send_ans_msg( $ans['msg']);
            }else{
                return_mag($ans['msg']);//尋不到相關問題 有種類
                $_pass=true;
            }
            $onoff_type=false;
        }else{
            // return_mag($ans['msg']);//很抱歉搜尋不到您所需的相關問題
        }
        if($onoff_type){
            switch ($Event['message']['text']) {
                case $pass['0']:
                    return_mag("顧客您好\n請幫我提供以下資訊\n姓名:\n電話:\n地址:\n機型:\n機號:\n購買日期:\n訂單編號:\n購買單位:\n故障狀況:\n謝謝");
                break;
                case '常見問題':
                    // $msg='功能建置中...';
                    common_problem();
                    break;
                case '線上諮詢':
                    common_QA_form();
                    // $msg='您好，請留言您的姓名、電話及地址，並提出您需要的協助，客服將在服務時間聯繫您！'."\n\n".'服務時間為：週一至週五'."\n".'上午8:30-12:00 下午13:00-17:30';
                    break;
                default:
                
                    $msg=otherQA($Event['message']['text']);
                    break;
                
            }
        }
        if($_pass==false){
            $_pass=fixMsg();
        }
        if(!empty($msg)){
            return_mag($msg);
        }else{
            if(!$_pass){
                return_mag('很抱歉現在，非服務時間，客服將在服務時間聯繫您！'."\n\n".'服務時間為：週一至週五'."\n".'上午8:30-12:00 下午13:00-17:30');
            }

        }

        
    }elseif($Event['type'] =='postback'){
        $msg=$Event['postback']['data'];
        $qa_list=['清單一般空調常見問題','清單商用空調常見問題','清單液晶螢幕常見問題','清單商用液晶螢幕常見問題','清單洗衣機常見問題',
                    '清單冷凍櫃常見問題','清單電冰箱常見問題','清單廚房家電常見問題','清單冬季家電常見問題','清單夏季家電常見問題',
                    '清單清潔家電常見問題','清單清淨除溼家電常見問題','清單其他生活家電常見問題'];
        if($_k=array_search($msg,$qa_list)){
            switch ($_k) {
                case 0:
                case 1:
                    common_QA_air();
                    break;
                case 2:
                    common_QA_mid(5);
                    break;
                case 3:
                    common_QA_mid(6);
                    break;
                case 4:
                    common_QA_mid(7);
                    break;
                case 5:
                    common_QA_mid(9);
                    break;
                case 6:
                    common_QA_mid(8);
                    break;
                case 7:
                    common_QA_kitchen();
                    break;
                case 8:
                    common_QA_winter();
                    break;
                case 9:
                    common_QA_summer();
                    break;
                case 10:
                    common_QA_clean();
                    break;
                case 11:
                    common_QA_Dehumidification();
                    break;
                case 12:
                    common_QA_other();
                    break;
            }
        }elseif(strpos($msg,'常見問題') !== false){
            foreach($G7_type as $k=>$_type_array){
                foreach($_type_array as $_type){
                    if(strpos($msg,$_type) !== false){
                        if($k<=3){
                            common_QA_air();
                        }else{
                            common_QA_mid($k);
                        }
                        break 2;
                    }
                }
            }
        }else if(!empty($msg)){
            return_mag($msg);
        }

    }
}
/**
 * 其他問題搜尋
 */
function otherQA($msg){
    $Key_box_array=[
        [['看','家電'],['看','電器'],['實','機','店家'],['實','機','販售'],['實體','販售'],['哪','現貨'],['哪','實','機'],['哪','實體'],['哪','展售'],['看','實物'],['看','實','機'],['看','實','體'],['門市','販售'],['經銷商','展示機'],'通路'],
        ['買遙控器','買濾心','電視腳架','電視底座','電池','水箱','充電線','冷凍櫃抽屜','旋鈕','扇葉','耗材','配件'],
    ];
    $ans=[
        '顧客您好'."\n".'本公司全省皆有授權的經銷商,但不會強制經銷商,擺設某款機型現場展示,如真的要看實體機,可能要先電洽當地經銷商,是否有現貨可看,謝謝',
        '顧客,您好'."\n".'如需購買配件可於上班時間洽詢0800-667-999#2，將由人員為您查詢及報價，謝謝您。'
    ];
    $return='';
    foreach($Key_box_array as $item=>$Key_array){
        foreach($Key_array as $_key){
            if(is_array($_key)){
                $_num=0;
                foreach( $_key as $_key_s){
                    if(strpos($msg,$_key_s) !== false){
                        $_num++;
                    }
                }
                if($_num == count($_key)){
                    $return=$ans[$item];
                    break 2;
                }
            }else{
                if(strpos($msg,$_key) !== false){
                    $return=$ans[$item];
                    break 2;
                }
            }
        }
    }
    return $return;

}
/**
 * 報修
 */
function fixMsg(){
    global $Event,$pass;
    $key_array=[
        '異常','故障','送修','停止運作','異音','壞了','無法開機','無法關機','沒有聲音','無畫面','代碼','短路','壞掉'
    ];
    if($Event['message']['text'] == $pass['0']||strpos($Event['message']['text'],'故障狀況') !== false){
        return true;
    }
    foreach($key_array as $key){
        
        if(strpos($Event['message']['text'],$key) !== false){
            quickReplies();
            return true;
        }
    }
    return false;
    // return_mag('12');
}

/**
 * 取得機器種類及編號
 */
function g7Type(){
    global  $conn;
    $G7_type=[];
    $sql='SELECT * FROM `line_type`';
    $expires_result = mysqli_query( $conn,$sql); 
    $return_array=['0'=>false,'msg'=>'很抱歉搜尋不到您所需的相關問題'];
    while ($row = mysqli_fetch_assoc($expires_result)) {
        $G7_type[$row['id']]=explode(',',$row['type']);
    }
    return  $G7_type;
}

function ansQA($text){
    global  $conn, $G7_type;
    // $sql="SELECT b.`id`,a.`type`,b.`key_word`,b.`question`,b.`answers`,b.`model` FROM `line_type`as a JOIN `line_QA_data` as b ON b.`type`= a.`id`";//全資訊
    $sql="SELECT b.`id`,a.`type`,b.`key_word` FROM `line_type`as a JOIN `line_QA_data` as b ON b.`type`= a.`id`";
    $expires_result = mysqli_query( $conn,$sql); 
    while ($row = mysqli_fetch_assoc($expires_result)) {
        $key_array=explode(',',$row['key_word']);
        $_count=0;
        $map_mid_s=false;
        foreach($key_array as $key){   
            if(strpos($text,$key) !== false){
                $_count++;
                $map_s=['id'=>$row['id'],'type'=>$row['type'],'nob'=> $_count];
                $_G7T=explode(',',$row['type']);
                foreach($_G7T as $_g7){
                    if(strpos($text,$_g7) !== false){
                        $map_mid_s=['id'=>$row['id'],'type'=>$row['type'],'nob'=> $_count];
                        break ;
                    }
                }
            }
        }
     
        if($map_s){$map[]=$map_s; unset($map_s);}
        if($map_mid_s){$map_mid[]=$map_mid_s;unset($map_mid_s);}
    }
    $return_array=['0'=>true,'msg'=>''];
    if(!empty($map_mid)){ //主要答案
        $return_array=['0'=>true,'msg'=>ansReturn($map_mid)];
    }elseif(!empty($map)){ //相關答案
        $return_array=['0'=>true,'msg'=>ansReturn($map)];
    }else{//找不到問題(機器種類)
        $return_array=['0'=>false,'msg'=>'很抱歉搜尋不到您所需的相關問題'];
        foreach($G7_type as $row){
                foreach( $row as $key){
                    if(strpos($text,$key) !== false){
                        $return_array=['0'=>true,'msg'=>'您好，若您有'.$key.'相關問題，請點選常見問題，'."\n".'若無法排除，請點選線上諮詢。'];
                        break 2;
                    }
                }

        }
    }
    return $return_array;
}
/**
 * ansQA 的過度
 */
function ansReturn($ans){
    global $conn;
    $G7_type=[];
    $sql='SELECT * FROM `line_type`';
    $expires_result = mysqli_query( $conn,$sql); 
    while ($row = mysqli_fetch_assoc($expires_result)) {
        $G7_type[$row['id']]=explode(',',$row['type']);
    }
    if(isset($ans['1'])){
        $id_array=$a=$sort_ans=$rt=[];
        foreach($ans as $_ans){
            $id_array[]=$_ans['id'];
            $a[(int)$_ans['nob']][]=$_ans['id'];
        }
        arsort($a);
        $sql="SELECT `id`,`type`,`question`,`answers` ,`model`FROM `line_QA_data` WHERE  `id`in (". implode(',',$id_array).")";
        $expires_result = mysqli_query( $conn,$sql); 
        while ($row = mysqli_fetch_assoc($expires_result)) {
            $rt[$row['id']]=$row;
        }
        foreach($a as $_k=>$sort_ans_a){
            foreach($sort_ans_a as $_k2=>$sort_ans_a2){
                $rt[$sort_ans_a2]['type']= $G7_type[$rt[$sort_ans_a2]['type']]['0'];
                $sort_ans[]= $rt[$sort_ans_a2];
            }
        }
        return['超過一個答案',json_encode( $sort_ans , 320)];

    }else{
        $sql="SELECT `id`,`type`,`question`,`answers`,`model` FROM `line_QA_data` WHERE  `id`='{$ans['0']['id']}'";
        $expires_result = mysqli_query( $conn,$sql); 
        while ($row = mysqli_fetch_assoc($expires_result)) {
            $row['type']= $G7_type[$row['type']]['0'];
            return['一個答案',json_encode($row , 320)];
        }
    }
}
/**
 * 轉訊息輸出
 */
function send_ans_msg($msg){
    global $Event,$G7_type;
    $_temp=false;
    $Payload =array(
        'replyToken' => $Event['replyToken'],
        'messages' => array([
                "type"=> "template",
                "altText"=> "this is a carousel template",
                "template"=> [
                    "type"=> "carousel",
                    "imageAspectRatio"=> "square",
                    "columns"=> []
                ]
        ])
    );
    {
        if($msg['0']=='超過一個答案'){
            $data=json_decode($msg['1'],true);
            foreach($data as $msg_data){
                if(empty($msg_data['model'])){
                    $add='';
                }else{
                    $add="\n \n適用機型(".$msg_data['model'].")";
                }
                $Payload['messages']['0']['template']['columns'][]=[
                    "title"  => $msg_data['type'].'問題',
                    "text"   => $msg_data['question'],
                    "actions"=> [
                            [
                                "type" => "postback",
                                "label"=> "點選解答",
                                "data" =>  $msg_data['answers'].$add
                            ]
                    ],
                ];
            }
            $_temp=true;
        }elseif($msg['0']=='一個答案'){
            $msg_data=json_decode($msg['1'],true);
            if(empty($msg_data['model'])){
                $add='';
            }else{
                $add="\n \n適用機型(".$msg_data['model'].")";
            }
            $Payload['messages']['0']['template']['columns'][]=[
                "title"  => $msg_data['type'].'問題',
                "text"   => $msg_data['question'],
                "actions"=> [
                        [
                            "type" => "postback",
                            "label"=> "點選解答",
                            "data" =>  $msg_data['answers'].$add
                        ]
                    ],
                ];

                $_temp=true;
        }
        
    }
    // var_dump($Payload);
    url_go($Payload);
    return $_temp;
}

/**棄用 */
{
    function html_Official_account(){
        global $Event;
        $Payload =array(
            'replyToken' => $Event['replyToken'],
            'messages' => array(
                array(
                    "type"=> "template",
                    "altText"=> "this is an image carousel template",
                    "template"=> [
                        // "imageAspectRatio"=> "rectangle",
                        "imageSize"=>"cover ",
                        "type"=> "image_carousel",
                        "columns"=> [
                            [
                                "imageUrl"=> URL_IMG."Official_account_FB.jpg",
                                "action"=> [
                                    "type"=> "uri",
                                    "label"=> "禾聯 HERAN ",
                                    "uri"=> "https://liff.line.me/1656605207-mjKD6oRe"
                                ]
                            ],
                            [ 
                                "imageUrl"=> URL_IMG."Official_account_IG.jpg",
                                "action"=> [
                                    "type"=> "uri",
                                    "label"=> "heran_taiwan",
                                    "uri"=> "https://liff.line.me/1656605207-qgJVBrb2"
                                ]
                                
                            ],
                            [
                                "imageUrl"=> URL_IMG."Official_account_YT.jpg",
                                "action"=> [
                                    "type"=> "uri",
                                    "label"=> "禾聯 HERAN ",
                                    "uri"=> "https://liff.line.me/1656605207-AdQdwGVL"
                                ]
                            ]
                        ]
                    ]
                )
            )
        );
        url_go($Payload);
    
    }
    function now_activity(){
        global $Event;
        $Payload =array(
            'replyToken' => $Event['replyToken'],
            'messages' => array([
                "type"=> "template",
                "altText"=> "this is a carousel template",
                "template"=> [
                    "type"=> "carousel",
                    "imageSize"=> "cover",
                    "imageAspectRatio"=> "rectangle",
                    "columns"=> [
                        [
                            "thumbnailImageUrl"=> URL_IMG."now_activity_10.jpg",
                            "text"=> "詳情請見活動頁面",
                            "actions"=> [
                                [
                                    "type"=> "uri",
                                    "label"=> "前往禾聯10倍送",
                                    "uri"=> "https://www.heran.com.tw/活動專區/decuple-voucher/"
                                ]
                            ]
                        ],
                        [
                            "thumbnailImageUrl"=> URL_IMG."now_activity_6c1.jpg",
                            "text"=> "詳情請見活動頁面",
                            "actions"=> [
                                [
                                    "type"=> "uri",
                                    "label"=> "前往好禮六選一",
                                    "uri"=> "https://www.heran.com.tw/活動專區/air-con-gift-event/"
                                ]
                            ]
                        ]
                    ]
                 ]
            ])
        );
        url_go($Payload);
    }
    function product_description(){
        global $Event;
        $Payload =array(
            'replyToken' => $Event['replyToken'],
            'messages' => array([
                "type"=> "template",
                "altText"=> "this is a carousel template",
                "template"=> [
                    "type"=> "carousel",
                    "imageSize"=> "cover",
                    "imageAspectRatio"=> "rectangle",
                    "columns"=> [
                    [
                        "thumbnailImageUrl"=> URL_IMG."product_description_1.jpg",
                        "title"=> "產品介紹",
                        "text"=> "請點選產品項目",
                        "actions"=> [
                        [
                            "type"=> "uri",
                            "label"=> "空調產品",
                            "uri"=> "https://liff.line.me/1656605207-GkQxBjqv"
                            // "uri"=> "https://www.heran.com.tw/product-category/air-conditioning/"
                        ],
                        [
                            "type"=> "uri",
                            "label"=> "液晶螢幕",
                            "uri"=> "https://liff.line.me/1656605207-Ze8D7J1m"
                            // "uri"=> "https://www.heran.com.tw/product-category/audiovisual/"
                        ],
                        [
                            "type"=> "uri",
                            "label"=> "電冰箱",
                            "uri"=> "https://liff.line.me/1656605207-O9dWrl7J"
                            // "uri"=> "https://www.heran.com.tw/product-category/refrigerator/"
                        ]
                        ]
                    ],
                    [
                        "thumbnailImageUrl"=> URL_IMG."product_description_2.jpg",
                        "title"=> "產品介紹",
                        "text"=> "請點選產品項目",
                        "actions"=> [
                        [
                            "type"=> "uri",
                            "label"=> "廚房家電",
                            "uri"=> "https://liff.line.me/1656605207-dEjmw9k4"
                            // "uri"=> "https://www.heran.com.tw/product-category/kitchen/"
                        ],
                        [
                            "type"=> "uri",
                            "label"=> "生活家電",
                            "uri"=> "https://liff.line.me/1656605207-D5WmX4rP"
                            // "uri"=> "https://www.heran.com.tw/product-category/life/"
                        ],
                        [
                            "type"=> "uri",
                            "label"=> "洗衣機",
                            "uri"=> "https://liff.line.me/1656605207-A1XR91Bv"
                            // "uri"=> "https://www.heran.com.tw/product-category/washing-machine/"
                        ]
                        ]
                    ],
                    [
                        "thumbnailImageUrl"=> URL_IMG."product_description_3.jpg",
                        "title"=> "產品介紹",
                        "text"=> "請點選產品項目",
                        "actions"=> [
                        [
                            "type"=> "uri",
                            "label"=> "冷凍櫃",
                            "uri"=> "https://liff.line.me/1656605207-LryNP307"
                            // "uri"=> "https://www.heran.com.tw/product-category/supplies/"
                        ],
                        [
                            "type"=> "uri",
                            "label"=> "商用空調",
                            "uri"=> "https://liff.line.me/1656605207-REj87Ope"
                            // "uri"=> "https://www.heran.com.tw/product-category/commercial-air-conditioning/"
                        ],
                        [
                            "type"=> "uri",
                            "label"=> "商用液晶",
                            "uri"=> "https://liff.line.me/1656605207-D4rpLYE5"
                            // "uri"=> "https://www.heran.com.tw/product-category/lcd-business/"
                        ]
                        ]
                    ]
                    ]
                ]
                      
                 
            ])
        );
        url_go($Payload);
    }
    
}


/**
 * 常見問題表單
 */
function common_problem(){
    global $Event;
    $Payload =array(
        'replyToken' => $Event['replyToken'],
        'messages' => array([
                "type"=> "template",
                "altText"=> "this is a carousel template",
                "template"=> [
                  "type"=> "carousel",
                  "imageSize"=> "cover",
                  "imageAspectRatio"=> "rectangle",
                  "columns"=> [
                    [
                        "thumbnailImageUrl"=> URL_IMG."ban-1.jpg",
                        "title"=> "常見問題",
                        "text"=> "請點選相關產品選項",
                        "actions"=> [
                            [
                                "type"=> "postback",
                                "label"=> "一般液晶螢幕",
                                "data"=> "清單液晶螢幕常見問題"
                            ], 
                            [
                                "type"=> "postback",
                                "label"=> "商用顯示器",
                                "data"=> "清單商用液晶螢幕常見問題"
                            ],
                            [
                                "type"=> "postback",
                                "label"=> "空調",
                                "data"=> "清單一般空調常見問題"
                            ],
                      ]
                    ],
                    [
                        "thumbnailImageUrl"=> URL_IMG."ban-2.jpg",
                        "title"=> "常見問題",
                        "text"=> "請點選相關產品選項",
                        "actions"=> [
                            [
                                "type"=> "postback",
                                "label"=> "洗衣機",
                                "data"=> "清單洗衣機常見問題"
                            ],
                            [

                                "type"=> "postback",
                                "label"=> "冷凍櫃",
                                "data"=> "清單冷凍櫃常見問題"
                            ],
                            [
                                "type"=> "postback",
                                "label"=> "電冰箱",
                                "data"=> "清單電冰箱常見問題"
                            ]
                        ]
                    ],
                    [
                        "thumbnailImageUrl"=> URL_IMG."ban-1.jpg",
                        "title"=> "常見問題",
                        "text"=> "請點選相關產品選項",
                        "actions"=> [
                            [
                                "type"=> "postback",
                                "label"=> "廚房家電",
                                "data"=> "清單廚房家電常見問題"
                            ],
                            [
                                "type"=> "postback",
                                "label"=> "冬季家電",
                                "data"=> "清單冬季家電常見問題"
                            ], 
                            [
                                "type"=> "postback",
                                "label"=> "夏季家電",
                                "data"=> "清單夏季家電常見問題"
                            ],
                        ]
                    ], 
                    [
                        "thumbnailImageUrl"=> URL_IMG."ban-2.jpg",
                        "title"=> "常見問題",
                        "text"=> "請點選相關產品選項",
                        "actions"=> [
                            [
                                "type"=> "postback",
                                "label"=> "清潔家電",
                                "data"=> "清單清潔家電常見問題"
                            ],
                            [
                                "type"=> "postback",
                                "label"=> "清淨除溼家電",
                                "data"=> "清單清淨除溼家電常見問題"
                            ],
                            [
                                "type"=> "postback",
                                "label"=> "其他生活家電",
                                "data"=> "清單其他生活家電常見問題"
                            ]
                        ]
                    ],
                  ]
                ]
        ])
    );
    url_go($Payload);
}
{ //常見問題 分類聚集
    function common_QA_air(){
        global $Event,$G7_type,$conn;
        $Payload =array(
            'replyToken' => $Event['replyToken'],
            'messages' => array([
                    "type"=> "template",
                    "altText"=> "this is a carousel template",
                    "template"=> [
                        "type"=> "carousel",
                        "imageAspectRatio"=> "square",
                        "columns"=> []
                    ]
            ])
        );
        $sql="SELECT * FROM `line_QA_data` WHERE`type` in (1,2,3)LIMIT 9";
        $expires_result = mysqli_query( $conn,$sql); 
        while ($msg_data = mysqli_fetch_assoc($expires_result)) {
            $msg_data['type']= $G7_type[$msg_data['type']]['0'];
            if(empty($msg_data['model'])){
                $add='';
            }else{
                $add="\n \n適用機型(".$msg_data['model'].")";
            }
            $Payload['messages']['0']['template']['columns'][]=[
                // "title"=>$G7_type[$msg_data['type']]['0'].'問題',
                "title"  => $msg_data['type'].'問題',
                "text"   => $msg_data['question'],
                "actions"=> [
                        [
                            "type" => "postback",
                            "label"=> "點選解答",
                            "data" =>  $msg_data['answers'].$add
                        ]
                ],
            ];
        }
        // var_dump(json_encode($Payload,320));
        url_go($Payload);
    }
    function common_QA_kitchen(){
        //    $_type=[ '咖啡機','磨豆機','熱水壺','隨行壺','電火鍋','氣炸烤箱','氣炸鍋','壓力鍋','電烤箱','洗碗機'];
        $sql="SELECT * FROM `line_type` WHERE`id` >=11 AND `id`<=19 LIMIT 9";
        common_QA_obj('廚房家電',$sql);
    }
    function common_QA_winter(){
        //    $_type=[ '陶瓷式電暖器','電熱毯',];
        $sql="SELECT * FROM `line_type` WHERE`id`=22 OR `id`=23";
        common_QA_obj('冬季家電',$sql);
    }
    function common_QA_summer(){
        //    $_type=[ '水冷扇','電風扇',];
        $sql="SELECT * FROM `line_type` WHERE`id`=20 OR `id`=21 ";
        common_QA_obj('夏季家電',$sql);
    }
    function common_QA_clean(){
        //    $_type=[ '掃地機','吸塵器',];
        $sql="SELECT * FROM `line_type` WHERE`id`=24 OR `id`=25 ";
        common_QA_obj('清潔家電',$sql);
    }
    function common_QA_Dehumidification(){
        //    $_type=[ '除濕機','清淨機',];
        $sql="SELECT * FROM `line_type` WHERE`id`=26 OR `id`=27 ";
        common_QA_obj('清淨除溼家電',$sql);
    }
    function common_QA_other(){
        //    $_type=[ '智能便座','',];
        $sql="SELECT * FROM `line_type` WHERE`id`=28 ";
        common_QA_obj('其他生活家電',$sql);
    }
    function common_QA_obj($title,$sql){
        global $Event,$conn;
        $Payload =array(
            'replyToken' => $Event['replyToken'],
            'messages' => array([
                    "type"=> "template",
                    "altText"=> "this is a carousel template",
                    "template"=> [
                        "type"=> "carousel",
                        "imageAspectRatio"=> "square",
                        "columns"=> []
                    ]
            ])
        );
        $expires_result = mysqli_query( $conn,$sql); 
        while ($_type= mysqli_fetch_assoc($expires_result)) {
            $Payload['messages']['0']['template']['columns'][]=[
                "title"=> $title."常見問題",
                "text"=> $_type['type'].'常見問題',
                "actions"=> [
                    [
                        "type"=>"postback",
                        "label"=> "點選解答",
                        "data"=> $_type['type'].'常見問題'
                    ]
                ],
            ];
        }
        // var_dump(json_encode( $Payload,320));
        url_go($Payload);
    }
}
/**
 * 常見問題各項
 */
function common_QA_mid($i){
    global $conn,$Event;
    $Payload =array(
        'replyToken' => $Event['replyToken'],
        'messages' => array([
                "type"=> "template",
                "altText"=> "this is a carousel template",
                "template"=> [
                    "type"=> "carousel",
                    "imageAspectRatio"=> "square",
                    "columns"=> [
                    ]
                ]
        ])
    );
    {
        $sql="SELECT `id`,`type`,`question`,`answers` ,`model`FROM `line_QA_data` WHERE  `type`= '$i' LIMIT 9";
        $expires_result = mysqli_query( $conn,$sql); 
        while ($row = mysqli_fetch_assoc($expires_result)) {
            if(empty($row['model'])){
                $add='';
            }else{
                $add="\n \n適用機型(".$row['model'].")";
            }
            $Payload['messages']['0']['template']['columns'][]=[
                "text"=>$row['question'],
                "actions"=> [
                    [
                        "type"=>"postback",
                        "label"=> "點選解答",
                        "data"=> $row['answers'].$add
                    ]
                ],
            ];
        }
    }
    // var_dump($Payload);
    url_go($Payload);
}

/**
 * 客服
 */
function common_QA_form(){
    global $Event;
    $Payload =array(
        'replyToken' => $Event['replyToken'],
        'messages' => array([
                "type"=> "template",
                "altText"=> "this is a carousel template",
                "template"=> [
                  "type"=> "carousel",
                //   "imageAspectRatio"=> "square",
                  "columns"=> [
                    [
                        "text"=> "請問您在官網上填過報修資料了嗎?",
                        "actions"=> [
                            [
                                "type"=> "postback",
                                // "type"=>"message",
                                "label"=> "是",
                                // "text"=> "請您等候客服人員與您聯繫。",
                                "data"=> "請您提供報修的姓名、電話與地址，客服人員將在服務時間回覆您。服務時間為 週一至週五 8:30-17:30"
                            ],
                            [
                                "type"=>"postback",
                                "label"=> "否",
                                "data"=> "請您提供報修的姓名、電話、地址與需要維修的產品狀況，或是相關圖片，客服人員將在服務時間回覆您。服務時間為 週一至週五 8:30-17:30"
                            ]
                        ],
                        // "thumbnailImageUrl"=> "PROVIDE_URL_FROM_YOUR_SERVER"
                    ],
                    [
                        "text"=> "撥打禾聯客服電話",
                        "actions"=> [
                            [
                                "type"=>"postback",
                                "label"=> "免付費客服專線",
                                "data"=> "請撥打0800-667-999 "
                            ],
                            [
                                "type"=>"postback",
                                "label"=> "全台服務據點專線",
                                "data"=> "【總公司服務部】連絡電話：(03)327-5407 \n\n【台北站】連絡電話：(02)6617-7860\n\n【宜蘭站】連絡電話：(03)958-5592\n\n【新竹站】連絡電話：(03)610-6383\n\n【台中站】連絡電話：(04)3609-1122\n\n【雲嘉站】連絡電話：(05)310-3755\n\n【台南站】連絡電話：(06)602-5789\n\n【高雄站】連絡電話：(07)963-1166\n\n【花蓮站】連絡電話：03-327-5407\n\n【台東站】連絡電話：(089)039-710\n\n【金門站】連絡電話：(07)963-1166\n\n【澎湖特約站】連絡電話：(07)963-1166\n\n"
                            ]
                        ],
                    ],
                  ]
                ]
        ])
    );
    url_go($Payload);
}

function return_mag($msg){
    global $Event;
    $Payload = [
        'replyToken' => $Event['replyToken'],
        'messages' => [
            [
                'type' => 'text',
                'text' => $msg
            ]
        ]
    ];
    url_go($Payload);
}
//video,audio
function return_img($img){
    global $Event;
    $Payload = [
        'replyToken' => $Event['replyToken'],
        'messages' => [
            [
                'type' => 'image',
                'originalContentUrl' => $img,
                'previewImageUrl' =>$img
            ]
        ]
    ];
    url_go($Payload);
}
function quickReplies(){
    global $Event,$user;
    // $user =getUser()['displayName'];
    $Payload = [
        'replyToken' => $Event['replyToken'],
        'messages' => [
            [
                "type"=> "text", 
                "text"=>  "{$user->getName()} 您好\n您是否需要線上報修?",
                "quickReply"=>[     
                    "items"=> [
                        [
                            "type"=> "action", 
                            "action"=> [
                                "uri"=> "https://liff.line.me/1656605207-Wy0nXpe5",
                                "type"=> "uri",
                                "label"=> "點我線上報修",
                            ]
                    
                        ],
                        [
                            "type"=> "action", 
                            // "imageUrl"=> "https://example.com/sushi.png",
                            // "uri"=> "https://liff.line.me/1656605207-b4GvNYzx",
                            // "label"=> "您是否需要線上報修?",
                            "action"=> [
                                // "uri"=> "https://liff.line.me/1656605207-b4GvNYzx",
                                "type"=> "message",
                                "label"=> "點我留下資料",
                                "text"=> "報修格式"
                            ]
                    
                        ],
                    ]
                ]
            ]
        ]
    ];
      url_go($Payload);
}

function getUser(){
    global $ChannelAccessToken, $Event;
    // 傳送訊息
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.line.me/v2/bot/profile/'.$Event['source']['userId']);
    // curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($Payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $ChannelAccessToken
    ]);
    $Result = json_decode( curl_exec($ch),true);
    curl_close($ch);
    // file_put_contents('log2.txt',json_encode( $ch,320)."\n",FILE_APPEND); 
    // var_dump($ch);
    return $Result;
}

/**
 * Function:curl GET 請求
 * @param $url
 * @param array $params
 * @param int $timeout
 * @return mixed
 * @throws Exception
 */
 function request_curl_get($url, $params = array(),$timeout=30){

    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

    $file_contents = curl_exec($ch);
    if($file_contents === false){
        throw new Exception('Http request message :'.curl_error($ch));
    }

    curl_close($ch);
    return $file_contents;

}

function url_go($Payload){
    global $ChannelAccessToken,$conn,$user,$Event;
    // 傳送訊息
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.line.me/v2/bot/message/reply');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($Payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $ChannelAccessToken
    ]);
    $Result = curl_exec($ch);
    curl_close($ch);
    if($Payload['messages']['0']['template']['columns']){
        $_temp='問答框:';
        foreach($Payload['messages']['0']['template']['columns'] as $as ){
            $_temp.=$as['text'].',';

        }
    }else{
        $_temp=$Payload['messages']['0']['text'];
    }
    $msg=addslashes($Event['message']['text']);
    if(empty($msg)){
        $msg='點擊問答框';
    }
    // var_dump($ch);
    $sql="INSERT INTO `line_msg`(`uid`, `name`, `msg`, `return_msg`, `time`, `img`) VALUES ('{$user->getUid()}','{$user->getName()}','{$msg}','{$_temp}',NOW(),'{$user->getImg()}')";
    // file_put_contents('log2.txt',$sql."\n",FILE_APPEND); 
    mysqli_query($conn,$sql);
    return $ch;
}

// function connectDB(){
//     $dbhost = 'localhost';
//     $dbuser = 'linejowinwin';
//     $dbpass = 'yC125hi4Dl7y';
//     $dbname = 'linejowi_line';
//     $conn=mysqli_connect($dbhost, $dbuser, $dbpass, $dbname)or die('Error with MySQL connection');
//     mysqli_set_charset($conn, "utf8");
//     return $conn;
// }
?>