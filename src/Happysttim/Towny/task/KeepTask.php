<?PHP

namespace Happysttim\Towny\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;

use Happysttim\Towny\Main;
use Happysttim\Towny\Towny;

class KeepTask extends PluginTask {
	
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
			    $keepMoney=$this->town->getKeepMoney($key);
			    $count=count($this->town->getAreas($key));
			    $payMoney=$this->town->getBaseKeepMoney()*$count;
			    if($keepMoney < $payMoney){
				    $this->town->broadcastMessage($key,self::PREFIX.Color::RED." 마을이 유지비 낼돈이 없어서 망했습니다");
				    $this->plugin->getServer()->broadcastMessage(self::PREFIX.Color::RED." {$key} 마을에서 유지비 낼 돈이 없어서 망했습니다");
				    $this->town->removeTown($key);
			    }else{
				    $this->town->delKeepMoney($key,$payMoney);
			    }
		    }
		}
		$this->plugin->getServer()->broadcastMessage(self::PREFIX.Color::YELLOW." 모든 마을에서 마을유지비를 걷었습니다");
		$this->town->save();
	}
}