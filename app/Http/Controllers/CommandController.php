<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Schema;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Http\Request;
use App\Command;
use Alert;
use Log;
use Telegram;
use Session;
use DB;

class CommandController extends Controller
{
    private $client, $iduser, $clientBot, $idchat, $fname, $titleGroup, $btcprice, $ethprice, $btchigh, $btclow, $ethhigh, $ethlow, $randomNum1, $randomNum2, $hasilCaptcha, $messageId, $volumeBtc, $volumeEth;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://www.luno.com/ajax/1/']);
        $this->clientBot = new Client(['base_uri' => 'https://api.telegram.org/bot'.env('TELEGRAM_BOT_TOKEN', 'YOUR-BOT-TOKEN').'/']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cmds = Command::paginate(5);
        return \view('commands.index', \compact('cmds'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return \view('commands.input');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'command'     => 'required|unique:commands|regex:/^\//',
            'message'  => 'required',
            'description'  => 'required',
            'link.*' => 'url|nullable'
        ]);

        try {
            $cmd = \trim($request->get('command'));
            $links = NULL;
            $link_titles = NULL;
            if (!is_null($request->link[0])) {
                $links = json_encode($request->get('link'));
                $link_titles = json_encode($request->get('link_title'));
            }
            $mes = Command::create([
                'command' => $cmd,
                'description' => $request->get('description'),
                'message' => $request->get('message'),
                'user_id' => Auth::user()->id,
                'links' => $links,
                'link_title' => $link_titles
            ]);

            Alert::success('Command succesfully inserted to a system', 'Yess..');

            return redirect()->route('command.index');
        } catch (\Exception $e) {
            Alert::error('Something went wrong'.$e->getMessage(), 'Oh no....');

            return redirect()->route('command.index');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $cmd = Command::findOrFail($id);

        return \view('commands.edit', \compact('cmd'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'command'     => 'required|unique:commands,command,'.$id.'|regex:/^\//',
            'message'  => 'required',
            'description'  => 'required',
            'link.*' => 'url|nullable'
        ]);

        try {
            $cmd = Command::findOrFail($id);
            $cmnd = \trim($request->get('command'));
            $cmd->command = $cmnd;
            $cmd->description = $request->get('description');
            $cmd->message = $request->get('message');
            if (!is_null($request->link[0])) {
                $cmd->links = json_encode($request->get('link'));
                $cmd->link_title = json_encode($request->get('link_title'));
            } else if (is_null($request->link[0])){
                $cmd->links = NULL;
                $cmd->link_title = NULL;
            }
            
            $cmd->save();
            Alert::success('Command succesfully Updated', 'Yess..');
    
            return redirect()->route('command.index');  
        } catch (\Exception $e) {
            Alert::error('Something went wrong', 'Oh no..');
    
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $cmd = Command::findOrFail($id);
            $cmd->delete();
            Alert::success('Command succesfully deleted', 'Yess..');
        } catch (Exception $th) {
            Alert::error('Command was not succesfully deleted', 'Ooohhh...');
        }

        return redirect()->back();
    }

    public function tess($res = NULL)
    {
        $response = Telegram::sendMessage([
            'chat_id' => $this->idchat, 
            'text' => '$res'
          ]);
          
        $messageId = $response->getMessageId();

        return $response;
    }

    public function sendMessage($message, $btn = NULL)
    {
        if (isset($message)) {
            $txt = \strip_tags($message, '<br><a><b><i><code><pre><strong><em><br/>');
            $txt = str_replace('<br>',"\n",$txt);
            $txt = str_replace('<br />',"\n",$txt);
            $txt = html_entity_decode($txt);
            $txt = str_replace('@fname@',$this->fname,$txt);
            $txt = str_replace('@grouptitle@',$this->titleGroup,$txt);
            $txt = str_replace('@bitcoinprice@',$this->btcprice,$txt);
            $txt = str_replace('@bitcoinhigh@',$this->btchigh,$txt);
            $txt = str_replace('@bitcoinlow@',$this->btclow,$txt);
            $txt = str_replace('@ethereumprice@',$this->ethprice,$txt);
            $txt = str_replace('@ethereumhigh@',$this->ethhigh,$txt);
            $txt = str_replace('@ethereumlow@',$this->ethlow,$txt);
            $txt = str_replace('@volumebtc@',$this->volumeBtc,$txt);
            $txt = str_replace('@volumeeth@',$this->volumeEth,$txt);
            $txt = str_replace('@date@',date('l, j F Y  H:i:s'),$txt);
            if (isset($btn)) {
                $response = Telegram::sendMessage([
                    'chat_id' => $this->idchat, 
                    'text' => $txt,
                    'parse_mode' => 'html',
                    'reply_markup'=>json_encode(['inline_keyboard'=>array($btn)])
                ]);
            } else {
                $response = Telegram::sendMessage([
                    'chat_id' => $this->idchat, 
                    'text' => $txt,
                    'parse_mode' => 'html'
                ]);
            }

            return $response;
        }
    }

    public function webhookUpdate()
    {
        $updates = Telegram::getWebhookUpdates();
        $cmd = $this->processMessage($updates);
        $msg = $this->getMessage($cmd);
        $btn = $this->getButton($cmd);
        
        return $this->sendMessage($msg,$btn);
    }

    /**
     * process message from webhook
     */
    public function processMessage($m)
    {
                if (isset($m["message"]["text"])) {
                    $sumber = $m["message"];
                    $cmd = $sumber["text"];
                    $this->iduser = $m["message"]["from"]["id"];
                    $this->idchat = $m["message"]["chat"]["id"];
                    $this->messageId = $sumber["message_id"];
                    $this->userData($sumber["chat"]["id"],$sumber["from"]["first_name"]);
                    // Log::info('iduser-'.$this->iduser.'{Process Message Before Session Check '.date('d-M-Y H:i:s').'}');
                    if($this->findSession('iduser'.$this->iduser)){
                        // Log::info('iduser-'.$this->iduser.'{Session has checked and return captcha function '.date('d-M-Y H:i:s').'}');
                        return $this->captcha($m);
                    } else {
                        // Log::info('iduser-'.$this->iduser.'{Session has checked and return false '.date('d-M-Y H:i:s').'}');
                        if (isset($m["message"]["entities"])) {
                            if ( $m["message"]["entities"][0]["type"] == "bot_command") {
                                if (strpos($cmd, env('TELEGRAM_BOT_USERNAME', 'YOUR-BOT-USERNAME')) !== false) {
                                    $cmd = str_replace(env('TELEGRAM_BOT_USERNAME', 'YOUR-BOT-USERNAME'), '', $cmd);
                                    return $cmd;
                                }else {
                                    return $cmd;   
                                }
                            }
                        }
                    }
                } elseif (isset($m["message"]["new_chat_member"])) {
                    $this->iduser = $m["message"]["from"]["id"];
                    $this->idchat = $m["message"]["chat"]["id"];
                    $this->userData($this->idchat,$m["message"]["new_chat_member"]["first_name"],$m["message"]["chat"]["title"]);
                    
                    return $this->captcha($m);
                }
        
    }

    public function getMessage($cmd)
    {
        if (isset($cmd)) {
            $message = Command::where('command', $cmd)->first();

            if ($cmd == "/tradingprice") {
                $this->dynamicData();
            }
            if (isset($message)) {
                return $message->message;
            } else {
                return 'The command was wrong';
            }
        }
    }

    public function getButton($cmd)
    {
        $message = Command::where('command', $cmd)->first();

        if (isset($message)) {
            if (\json_decode($message->links)) {
                $link = \json_decode($message->links);
                $title = \json_decode($message->link_title);
                for ($i=0; $i < count(\json_decode($message->links)); $i++) { 
                    $url[$i]['text'] = $title[$i];
                    $url[$i]['url'] = $link[$i];
                }
                return $url;
            }
        }
    }

    public function setEndpoint()
    {
        $this->client = new Client(['base_uri' => 'https://api.telegram.org/bot'.env('TELEGRAM_BOT_TOKEN', 'YOUR-BOT-TOKEN').'/']);
    }

    public function kickMember($iduser)
    {
        if ($this->checkAdmin($iduser) != true) {
            try {
                $this->setEndpoint();
                $this->client->request('POST', 'kickChatMember', [
                    'query' => [
                        'chat_id' => $this->idchat,
                        'user_id' => $iduser,
                        'until_date' => time() + (15* 60)
                    ]
                ]);
            } catch (Exception $e) {
                echo($e);
            }
        }
    }

    public function getPrice($crpt='XBT', $cur='MYR')
    {
        $response = $this->client->request('GET', 'price_chart');

        if ($response->getStatusCode() == 200) {
            $body = (string) $response->getBody()->getContents();
            $data =  json_decode($body, true);
            $a = $data["availablePairs"];
            foreach ($a as $v ) {
                if ($v["counterCode"] == $cur && $v["baseCode"] == $crpt) {
                    return $this->formatcurrency($v["price"]);
                }
            }
        } else {
            return false;
        }
    }

    public function getHilo($crpt='XBT', $cur='MYR')
    {
        $response = $this->client->request('GET', 'charts_candles', [
            'query' => [
                'pair' => $crpt.$cur,
                'since' => strtotime("-1 day")
            ]
        ]);

        if ($response->getStatusCode() == 200) {
            $body = (string) $response->getBody()->getContents();
            $data =  json_decode($body, true);
            
            $high = array();
            $low = array();
            foreach ($data["candles"] as $v) {
                $high[] = $v["high"];
                $low[] = $v["low"];
            }
            $price = array(
                "High"=> $this->formatcurrency(max($high)), 
                "Low"=> $this->formatcurrency(min($low)));
            return $price;
        } else {
            return false;
        }
    }

    public function getVolume($crpt='XBT', $cur='MYR')
    {
        $client = new Client();
        $response = $client->request('GET', 'https://api.mybitx.com/api/1/ticker', [
            'query' => [
                'pair' => $crpt.$cur
            ]
        ]);

        if ($response->getStatusCode() == 200) {
            $body = (string) $response->getBody()->getContents();
            $data =  json_decode($body, true);
            $a = $data["rolling_24_hour_volume"];
            return $a;
        } else {
            return false;
        }
    }

    public function formatcurrency($floatcurr){
        return "MYR " . number_format($floatcurr,1,'.',',');
    }

    public function dynamicData()
    {
        $priceBtc = $this->getHilo();
        $priceEth = $this->getHilo('ETH');
        $volumeBtc = $this->getVolume();
        $volumeEth = $this->getVolume('ETH');

        $this->btcprice = $this->getPrice();
        $this->btchigh = $priceBtc["High"];
        $this->btclow = $priceBtc["Low"];
        
        $this->ethprice = $this->getPrice('ETH');
        $this->ethhigh = $priceEth["High"];
        $this->ethlow = $priceEth["Low"];

        $this->volumeBtc = $volumeBtc;
        $this->volumeEth = $volumeEth;
    }

    public function userData($idchat, $fname, $titleGroup = NULL)
    {
        $this->idchat = $idchat;
        $this->fname = $fname;
        $this->titleGroup = $titleGroup;
    }

    public function captcha($m)
    {
        if($this->findSession('iduser'.$this->iduser)){
            $id = $this->findSession('iduser'.$this->iduser);
            $captcha = $this->findSession('captcha'.$this->iduser);
            $time = time() - $this->findSession('time'.$this->iduser);
            $messageid = $this->findSession('messageid'.$this->iduser);
            $this->deleteSession('messageid'.$this->iduser);
            if ($time > 60) {
                // Log::info('iduser-'.$this->iduser.'{Removing Member '.date('d-M-Y H:i:s').'}');
                $this->removeMessage($this->idchat,$messageid);
                $this->removeMessage($this->idchat,$this->findSession('usermessage'.$this->iduser));
                $this->kickMember($id);
                $this->deleteSession('messageid'.$this->iduser);
                $this->deleteSession('time'.$this->iduser);
                $this->deleteSession('iduser'.$this->iduser);
                $this->deleteSession('captcha'.$this->iduser);
                $this->deleteSession('usermessage'.$this->iduser);
                return NULL;
            } else {
                // Log::info('iduser-'.$this->iduser.'{Answering Captcha '.date('d-M-Y H:i:s').'}');
                if (isset($m["message"]["text"])) {
                    $sumber = $m["message"];
                    $cmd = $sumber["text"];
                    if ($cmd != $captcha) {
                        if ($this->countAmount($this->iduser)) {
                            $this->removeMessage($this->idchat,$messageid);
                            // $this->removeMessage($this->idchat,$this->findSession('usermessage'.$this->iduser));
                            $this->kickMember($id);
                            $this->deleteSession('messageid'.$this->iduser);
                            $this->deleteSession('time'.$this->iduser);
                            $this->deleteSession('iduser'.$this->iduser);
                            $this->deleteSession('captcha'.$this->iduser);
                            $this->deleteSession('usermessage'.$this->iduser);
                            $this->deleteAmount($this->iduser);
                            return NULL;
                        }
                        $this->insertAmount($this->iduser, '1');
                        $this->deleteSession('captcha'.$this->iduser);
                        $randomNum1 = \rand(0,9);
                        $randomNum2 = \rand(0,9);
                        $this->hasilCaptcha = $randomNum1 + $randomNum2;
                        
                        $this->insertSession('captcha'.$this->iduser, $this->hasilCaptcha);
                        $txt = "Hi $this->fname, Your answer is wrong please try another number \n";
                        $txt .= "$randomNum1 + $randomNum2 = ? \n ";
                        $txt .= "If you don't answer then you will be kicked after 60 seconds.";
                        $response = Telegram::sendMessage([
                            'chat_id' => $this->idchat, 
                            'text' => $txt
                        ]);
                        if ($this->findSession('usermessage'.$this->iduser)) {
                            $this->removeMessage($this->idchat,$this->findSession('usermessage'.$this->iduser));
                            $this->deleteSession('usermessage'.$this->iduser);
                            $this->insertSession('usermessage'.$this->iduser, $m["message"]["message_id"]);
                        } else {
                            $this->insertSession('usermessage'.$this->iduser, $m["message"]["message_id"]);
                        }
                        $this->insertSession('messageid'.$this->iduser, $response->getMessageId());
                        $this->removeMessage($this->idchat,$messageid);
                        return NULL;
                    }else {
                        $this->removeMessage($this->idchat,$messageid);
                        $this->removeMessage($this->idchat,$this->findSession('usermessage'.$this->iduser));
                        $this->deleteSession('messageid'.$this->iduser);
                        $this->deleteSession('time'.$this->iduser);
                        $this->deleteSession('iduser'.$this->iduser);
                        $this->deleteSession('captcha'.$this->iduser);
                        $this->deleteSession('usermessage'.$this->iduser);
                        $this->deleteAmount($this->iduser);
                        return '@welcome';
                    }
                }
            }
		}else{
            $idbot = env('TELEGRAM_BOT_TOKEN', 'NULL');
            $idbot = explode(":", $idbot);
            $idbot = $idbot[0];
            $i = $m["message"]["new_chat_member"]["id"];
            if ($i != $idbot) {
                // Log::info('iduser-'.$this->iduser.'{New Members '.date('d-M-Y H:i:s').'}');
                $this->insertSession('iduser'.$this->iduser, $this->iduser);
                
                $randomNum1 = \rand(0,9);
                $randomNum2 = \rand(0,9);
                $this->hasilCaptcha = $randomNum1 + $randomNum2;
                
                $this->insertSession('captcha'.$this->iduser, $this->hasilCaptcha);
                $txt = "Hi $this->fname, To verify that you are a human, then you must answer this mathematical operation in 60 seconds. \n";
                $txt .= "$randomNum1 + $randomNum2 = ? \n ";
                $txt .= "If you don't answer then you will be kicked after 60 seconds.";
                $response = Telegram::sendMessage([
                    'chat_id' => $this->idchat, 
                    'text' => $txt
                ]);
                $this->insertSession('messageid'.$this->iduser, $response->getMessageId());
                $this->insertSession('time'.$this->iduser, time());
            }
            
            return NULL;
		}
    }

    public function removeMessage($idchat, $idmessage)
    {
        if (isset($idmessage) && $idmessage != 0) {
            try {
                $response = $this->clientBot->request('GET', 'deleteMessage', [
                    'query' => [
                        'chat_id' => $idchat,
                        'message_id' => $idmessage,
                    ]
                ]);
            } catch (Exception $e) {
                Log::warning("{can't delete message .error message($e) ".date('d-M-Y H:i:s')."}");
            }
            
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editWelcomeMessage()
    {
        $cmd = Command::where('command', '@welcome')->first();

        return \view('commands.welcome-message', \compact('cmd'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateWelcomeMessage(Request $request)
    {
        $this->validate($request, [
            'message'  => 'required',
            'link.*' => 'url|nullable'
        ]);

        try {
            $cmd = Command::where('command', '@welcome')->first();
            $cmd->message = $request->get('message');
            if (!is_null($request->link[0])) {
                $cmd->links = json_encode($request->get('link'));
                $cmd->link_title = json_encode($request->get('link_title'));
            } else if (is_null($request->link[0])){
                $cmd->links = NULL;
                $cmd->link_title = NULL;
            }
            
            $cmd->save();
            Alert::success('Command succesfully Updated', 'Yess..');
    
            return redirect()->route('command.index');  
        } catch (\Exception $e) {
            Alert::error('Something went wrong', 'Oh no..');
    
            return redirect()->back();
        }
    }

    public function insertSession($key, $val)
    {
        try {
            DB::connection('sqlite')->table('session')->insert([
                'key' => $key,
                'value' => $val
            ]);
            return true;
        } catch (Exception $e) {
            Log::warning("{key : $key and value : $val can't insert session .error message($e) ".date('d-M-Y H:i:s')."}");
            return false;
        }
        
    }

    public function insertAmount($key, $val)
    {
        try {
            DB::connection('sqlite')->table('answer_amount')->insert([
                'key' => $key,
                'value' => $val
            ]);
            return true;
        } catch (Exception $e) {
            Log::warning("{key : $key and value : $val can't insert amount .error message($e) ".date('d-M-Y H:i:s')."}");
            return false;
        }
        
    }

    public function findSession($key)
    {
        try {
            $v = DB::connection('sqlite')->table('session')->where('key', $key)->select('value')->first();
            if ($v) {
                return $v->value;
            } else {
                return false;
            }
            
        } catch (Exception $e) {
            Log::warning("{key : $key can't check error message($e) ".date('d-M-Y H:i:s')."}");
            return false;
        }
    }

    public function countAmount($key)
    {
        try {
            $v = DB::connection('sqlite')->table('answer_amount')->where('key', $key)->get();
            if ($v) {
                if ($v->count() == 2) {
                    return true;
                } else {
                    return false;
                }
            }
        } catch (Exception $e) {
            Log::warning("{key : $key can't count amount. error message($e) ".date('d-M-Y H:i:s')."}");
            return false;
        }
    }

    public function deleteSession($key)
    {
        try {
            DB::connection('sqlite')->table('session')->where('key', $key)->delete();
            return true;
        } catch (Exception $e) {
            Log::warning("{key : $key can't delete error message($e) ".date('d-M-Y H:i:s')."}");
            return false;
        }
    }

    public function deleteAmount($key)
    {
        try {
            DB::connection('sqlite')->table('answer_amount')->where('key', $key)->delete();
            return true;
        } catch (Exception $e) {
            Log::warning("{key : $key can't delete amount. error message($e) ".date('d-M-Y H:i:s')."}");
            return false;
        }
    }

    public function createSqlite()
    {
        try {
            Schema::connection('sqlite')->create('session', function($table)
            {
                $table->string('key', 255);
                $table->primary('key');
                $table->integer('value');
                $table->timestamps();
            });

            Schema::connection('sqlite')->create('answer_amount', function($table)
            {
                $table->bigIncrements('id');
                $table->string('key', 255);
                $table->integer('value');
                $table->timestamps();
            });
            echo 'Database Sqlite created';
        } catch (Exception $e) {
            echo $e;
        }
    }

    public function truncateSqlite()
    {
        try {
            DB::connection('sqlite')->table('session')->truncate();
            DB::connection('sqlite')->table('answer_amount')->truncate();
            echo 'Database Sqlite truncated';
        } catch (Exception $e) {
            echo $e;
        }
    }

    public function checkAdmin($iduser)
    {
        $client = new Client();
        $response = $client->request('GET', 'https://api.telegram.org/bot'.env('TELEGRAM_BOT_TOKEN', 'YOUR-BOT-TOKEN').'/getChatAdministrators', [
            'query' => [
                'chat_id' => $this->idchat
            ]
        ]);

        if ($response->getStatusCode() == 200) {
            $body = (string) $response->getBody()->getContents();
            $data =  json_decode($body, true);
            $a = $data["result"];
            foreach ($a as $value) {
                if ($iduser == $value["user"]["id"]) {
                    return true;
                    break;
                }
            }
        }
    }
}
