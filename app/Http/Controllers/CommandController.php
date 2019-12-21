<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Http\Request;
use App\Command;
use Alert;
use Telegram;
use Session;

class CommandController extends Controller
{
    private $client, $iduser, $clientBot, $idchat, $fname, $titleGroup, $btcprice, $ethprice, $btchigh, $btclow, $ethhigh, $ethlow, $randomNum1, $randomNum2, $hasilCaptcha, $messageId;

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
        try {
            if ($m["message"]["from"]["is_bot"] == false) {
                if (isset($m["message"]["entities"])) {
                    $sumber = $m["message"];
                    $cmd = $sumber['text'];
                    $this->iduser = $m["message"]["from"]["id"];
                    $this->messageId = $sumber["message_id"];
                    $this->dynamicData($sumber["chat"]["id"],$sumber["from"]["first_name"]);
                    if(!Session::has('iduser'.$this->iduser)){
                        if ( $m["message"]["entities"][0]["type"] == "bot_command") {
                            if (strpos($cmd, env('TELEGRAM_BOT_USERNAME', 'YOUR-BOT-USERNAME')) !== false) {
                                $cmd = str_replace(env('TELEGRAM_BOT_USERNAME', 'YOUR-BOT-USERNAME'), '', $cmd);
                                return $cmd;
                            }else {
                                return $cmd;   
                            }
                        }
                    } else {
                        return $this->captcha($m);
                    }
                } elseif (isset($m["message"]["new_chat_member"])) {
                    $this->iduser = $m["message"]["from"]["id"];
                    $this->idchat = $m["message"]["chat"]["id"];
                    $this->dynamicData($m["message"]["chat"]["id"],$m["message"]["new_chat_member"]["first_name"],$m["message"]["chat"]["title"]);
                    
                    return $this->captcha($m);
                }  
            }
        } catch (Exception $e) {
            return $e;
        }
        
    }

    public function getMessage($cmd)
    {
        if (isset($cmd)) {
            $message = Command::where('command', $cmd)->first();

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
        $this->setEndpoint();
        $this->client->request('POST', 'kickChatMember', [
            'query' => [
                'chat_id' => $this->idchat,
                'user_id' => $iduser,
            ]
        ]);
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

    public function formatcurrency($floatcurr){
        return "MYR " . number_format($floatcurr,1,'.',',');
    }

    public function dynamicData($idchat, $fname, $titleGroup = NULL)
    {
        $priceBtc = $this->getHilo();
        $priceEth = $this->getHilo('ETH');

        $this->idchat = $idchat;
        $this->fname = $fname;
        $this->titleGroup = $titleGroup;

        $this->btcprice = $this->getPrice();
        $this->btchigh = $priceBtc["High"];
        $this->btclow = $priceBtc["Low"];
        
        $this->ethprice = $this->getPrice('ETH');
        $this->ethhigh = $priceEth["High"];
        $this->ethlow = $priceEth["Low"];
    }

    public function captcha($m)
    {
        if(Session::has('iduser'.$this->iduser)){
            $id = Session::get('iduser'.$this->iduser);
            $captcha = Session::get('captcha'.$this->iduser);
            $time = time() - Session::get('time'.$this->iduser);
            $messageid = Session::get('messageid'.$this->iduser);
            Session::forget('messageid'.$this->iduser);
            if ($time > 60) {
                $this->removeMessage($this->idchat,$messageid);
                // $this->kickMember($id);
                Session::forget('messageid'.$this->iduser);
                Session::forget('time'.$this->iduser);
                Session::forget('iduser'.$this->iduser);
                Session::forget('captcha'.$this->iduser);
                return 'you have been kicked';
            } else {
                if (isset($m["message"]["entities"])) {
                    $sumber = $m["message"];
                    $cmd = $sumber['text'];
                    if ($cmd != $captcha) {
                        $response = Telegram::sendMessage([
                            'chat_id' => $this->idchat, 
                            'text' => 'Your answer is wrong',
                            'reply_to_message_id' => $this->messageId
                        ]);
                        Session::put('messageid'.$this->iduser, $response->getMessageId());
                        $this->removeMessage($this->idchat,$messageid);
                        return NULL;
                    }else {
                        $this->removeMessage($this->idchat,$messageid);
                        Session::forget('messageid'.$this->iduser);
                        Session::forget('time'.$this->iduser);
                        Session::forget('iduser'.$this->iduser);
                        Session::forget('captcha'.$this->iduser);
                        return '@welcome';
                    }
                }
            }
		}else{
            Session::put('iduser'.$this->iduser, $this->iduser);
            
            $randomNum1 = \rand(0,9);
            $randomNum2 = \rand(0,9);
            $this->hasilCaptcha = $randomNum1 + $randomNum2;
            
            Session::put('captcha'.$this->iduser, $this->hasilCaptcha);
            $txt = "Hi $this->fname, To verify that you are a human, then you must answer this mathematical operation in 60 seconds. \n";
            $txt .= " $randomNum1 + $randomNum2 = ? \n ";
            $txt .= " If you don't answer then you will be kicked";
            $response = Telegram::sendMessage([
                'chat_id' => $this->idchat, 
                'text' => $txt
            ]);
            Session::put('messageid'.$this->iduser, $response->getMessageId());
            Session::put('time'.$this->iduser, time());
		}
    }

    public function removeMessage($idchat, $idmessage)
    {
        $response = $this->clientBot->request('GET', 'deleteMessage', [
            'query' => [
                'chat_id' => $idchat,
                'message_id' => $idmessage,
            ]
        ]);
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

    public function tes()
    {
        Timer::start('full');

        Timer::start('laps');
        sleep(1);
        Timer::stop('laps');
        
        sleep(2); // This time is not calculated under 'laps'
        
        Timer::start('laps');
        sleep(1);
        Timer::stop('laps');
        
        echo round(Timer::read('full', Timer::FORMAT_SECONDS),0); // 4 seconds.
        echo "<br />";
        echo Timer::read('laps', Timer::FORMAT_SECONDS); // 2 seconds (1 + 1)
    }
}
