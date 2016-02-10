<?PHP

namespace Happysttim\Towny\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\Listener;

use Happysttim\Towny\Main;
use Happysttim\Towny\Towny;
use Happysttim\Towny\event\player\PlayerJoinTownyEvent;
use Happysttim\Towny\event\player\PlayerQuitTownyEvent;

class PositionTask extends PluginTask implements Listener{
	
	private $town,$plugin;
	private $last=[];
	
	public function __construct(Main $main){	
		$this->plugin=$main;
		$this->town=Towny::getInstance();
		$this->plugin->getServer()->getPluginManager()->registerEvents($this,$this->plugin);
		parent::__construct($main);
	}
	public function onRun($currentTick){
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			if($player->getLevel()->getFolderName()!=$this->town->getLevelName()) continue;
			$where=$this->town->getPlayerPosition($player->getX() >> 4 , $player->getZ() >> 4);
			if(!isset($this->last[$player->getName()])){
				$this->last[$player->getName()]=$where;
				$this->plugin->getServer()->getPluginManager()->callEvent(new PlayerJoinTownyEvent($this->plugin,$player,$where));
				$player->sendMessage(Color::AQUA."<~~~~~~~~");
				$player->sendMessage(Color::RED."~ ".Color::YELLOW.$where." ".Color::RED."~");
				$player->sendMessage(Color::AQUA."~~~~~~~~>");
			}
			if($this->last[$player->getName()]!=$where){
				$temp=$this->last[$player->getName()];
				$this->last[$player->getName()]=$where;
				$this->plugin->getServer()->getPluginManager()->callEvent(new PlayerJoinTownyEvent($this->plugin,$player,$where));
				$this->plugin->getServer()->getPluginManager()->callEvent(new PlayerQuitTownyEvent($this->plugin,$player,$temp));
				$player->sendMessage(Color::AQUA."<~~~~~~~~");
				$player->sendMessage(Color::RED."~ ".Color::YELLOW.$where." ".Color::RED."~");
				$player->sendMessage(Color::AQUA."~~~~~~~~>");
			}
		}
	}
}