<?php
    session_start();
    require __DIR__ . '/vendor/autoload.php';
     
    use \LINE\LINEBot;
    use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
    use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
    use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
    use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
    use \LINE\LINEBot\SignatureValidator as SignatureValidator;
    use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
    use \LINE\LINEBot\MessageBuilder\VideoMessageBuilder;

    //database
    function pg_connection_string()
    {
        $host = "ec2-52-201-124-168.compute-1.amazonaws.com";
        $pass = "ad4bc05f6da65f16ab37323c30dd2bef03a7b7b7cd48e5e95747473710ec3110";
        $user = "acnbyycwkilhes";
        $dbname = "dc9krdsmcb7qtr";
        return "dbname=$dbname host=$host port=5432 user=$user password=$pass sslmode=require";
    }


    // set false for production
    $pass_signature = true;
     
    // set LINE channel_access_token and channel_secret
    $channel_access_token = "fXqWlxOmi+RlDFrLiycxU2sc0Om8DPPiNhlDTskRqLOZc0AizmunHx0pxlbSxdlENy0Bu2BCKYtVDV2ASh7Lq0SJ8Cg0VtpexuMdrD4Y7xVPOcWH0bx1EWsrNul7wvHxZDwffekVIF4jOJi5pKW+3gdB04t89/1O/w1cDnyilFU=";
    $channel_secret = "8fc78406c9970a7cb93cf8030be3cac1";
     
    // inisiasi objek bot
    $httpClient = new CurlHTTPClient($channel_access_token);
    $bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);
     
    $configs =  [
        'settings' => ['displayErrorDetails' => true],
    ];
    $app = new Slim\App($configs);
     
    // buat route untuk url homepage
    $app->get('/', function($req, $res)
    {
      echo "Welcome ... Status OK";
    });
     
    // buat route untuk chatbot
    $app->post('/chatbot', function ($request, $response) use ($bot, $httpClient) 
    {

         // get connect database
        $db = pg_connect(pg_connection_string());

        // get request body and line signature header
        $body        = file_get_contents('php://input');
        $signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : '';
     
        // log body and signature
        file_put_contents('php://stderr', 'Body: '.$body);
     
        if($pass_signature === false)
        {
            // is LINE_SIGNATURE exists in request header?
            if(empty($signature)){
                return $response->withStatus(400, 'Signature not set');
            }
     
            // is this request comes from LINE?
            if(! SignatureValidator::validateSignature($body, $channel_secret, $signature)){
                return $response->withStatus(400, 'Invalid signature');
            }
        }
     
        // kode aplikasi nanti disini

        $data = json_decode($body, true);
if(is_array($data['events'])){
    foreach ($data['events'] as $event)
    {
         //id
         $uid = $event['source']['userId'];
         $getprofile = $bot->getProfile($uid);
         $profile = $getprofile->getJSONDecodedBody();

        if ($event['type'] == 'message')
        {
            if($event['message']['type'] == 'text')
            {
                // send same message as reply to user
                // $result = $bot->replyText($event['replyToken'], $event['message']['text']);
                // $bot->replyText($event['replyToken'], "hello");
                // or we can use replyMessage() instead to send reply message
                // $textMessageBuilder = new TextMessageBuilder($event['message']['text']);
                // $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
               /*  $textMessageBuilder1 = new TextMessageBuilder('ini pesan balasan pertama');
                 $textMessageBuilder2 = new TextMessageBuilder('ini pesan balasan kedua');
                 $stickerMessageBuilder = new StickerMessageBuilder(11537, 52002759);
                 $stickerMessageBuilder2 = new StickerMessageBuilder(1, 126);

                 $multiMessageBuilder = new MultiMessageBuilder();
                 $multiMessageBuilder->add($textMessageBuilder1);
                 $multiMessageBuilder->add($textMessageBuilder2);
                 $multiMessageBuilder->add($stickerMessageBuilder);
                 $multiMessageBuilder->add($stickerMessageBuilder2);
*/

                // $videoMessageBuilder = new VideoMessageBuilder('https://youtu.be/CMtRrhEbJjw', 'https://file-examples.com/wp-content/uploads/2017/10/file_example_JPG_100kB.jpg');
              //   $result =  $result = $bot->replyMessage($event['replyToken'], $multiMessageBuilder);

              /*
                $tambah=$event['message']['text'];
                $a=explode("+",$tambah);
                $b=$a[0]+$a[1];

                $bot->replyText($event['replyToken'], $b);
                */
                    
                 //flex
                // require __DIR__ . '/flex/flex_message.php';
               /*
                    $flex = file_get_contents("flex_message.json");
                  
                     $result = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', [
                        'replyToken' => $event['replyToken'],
                        'messages' => [
                            [
                                'type' => 'flex',
                                'altText' => 'Test Flex Message',
                                'contents' => json_decode($flex),
                            ],
                        ],
                    ]);
                   */                          
                        //end flex
                    
                        if (!$db) {
                            $result = $bot->replyText($event['replyToken'], pg_connection_string());
                        } else {
                           
                            $usr = $profile['displayName'];
                            $result = $bot->replyText($event['replyToken'], $usr);
                            $kata =strtolower($event['message']['text']);
                            $query_t = pg_query($db, "SELECT kata  ,jawab  FROM chatbot where kata='$kata'  ");

                            $row = pg_fetch_array($query_t, 0, PGSQL_NUM);

                            if($row[0])
                            {
                            $result = $bot->replyText($event['replyToken'], $row[1]);
                            }
                            else
                            {
                                $result = $bot->replyText($event['replyToken'], $usr.' Kata yang anda ketik salah');
                            }

                             
                        }
                
            /*
                if($event['message']['text']=="input")
                {
                    $bot->replyText($event['replyToken'], "input");
                }
                else{
                    $bot->replyText($event['replyToken'], "bukan input");
                }
            */
                

            return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
            }
        }
    } 
}
return $response->withStatus(400, 'No event sent!');
     //end kode
    });
     
    $app->run();