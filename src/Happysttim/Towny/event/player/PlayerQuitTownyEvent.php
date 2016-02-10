<?PHP

namespace Happysttim\Towny\event\player;

use pocketmine\Player;

use Happysttim\Towny\event\TownyPlayerEvent;
use Happysttim\Towny\Main;

class PlayerQuitTownyEvent extends TownyPlayerEvent {

	private $town;
	public static $handlerList;
	
	public function __construct(Main $plugin,Player $player,$town){
		parent::__construct($plugin,$player);
		
		$this->town=$town;
	}
	public function getTown(){
		return $this->town;
	}
}