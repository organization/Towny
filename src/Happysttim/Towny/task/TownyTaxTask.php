<?PHP

namespace Happysttim\Towny\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;

use Happysttim\Towny\Main;
use Happysttim\Towny\Towny;

class TownyTaxTask extends PluginTask {
	
	private $town,$plugin,$money;
	
	const PREFIX=Color::YELLOW."[Towny]";
	
	public function __construct(Main $main,$money){	
		$this->plugin=$main;
		$this->money=$money;
		$this->town=Towny::getInstance();
		parent::__construct($main);
	}
	public function onRun($currentTick){
		if(count($this->town->getTowns()) > 0){
		    foreach($this->town->getTowns() as $key=>$value){
			    foreach($this->town->getMember($key) as $member){
					if($this->town->getOwner($key)==$member) continue;
				    $money=$this->money->myMoney($member);
				    $tax=$this->town->getTax($key);
				    if($money < $tax){
					    $this->town->broadcastMessage($key,self::PREFIX.Color::RED." {$member} 님이 세금 낼 돈이 없어서 추방당했습니다");
					    $this->town->delMember($key,$member);
				    }else{
					    $this->money->reduceMoney($member,$tax);
					    $this->town->addKeepMoney($key,$tax);
					    if(($player=$this->plugin->getServer()->getPlayerExact($member)) instanceof Player){
						    $player->sendMessage(self::PREFIX.Color::GOLD." {$tax} 원 어치의 세금을 내셨습니다!");
					    }
				    }
			    }
		    }
		}
		$this->plugin->getServer()->broadcastMessage(self::PREFIX.Color::YELLOW." 모든 마을에서 세금을 걷었습니다");
		$this->town->save();
	}
}