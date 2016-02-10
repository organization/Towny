<?PHP

namespace Happysttim\Towny\event\towny;

use pocketmine\Player;

use Happysttim\Towny\event\TownyEvent;
use Happysttim\Towny\Main;

class TownyRemoveEvent extends TownyEvent {

	private $town,$who;
	public static $handlerList;
	
	public function __construct(Main $plugin,$owner,$town){
		parent::__construct($plugin,$owner);
		
		$this->town=$town;
	}
	public function getTown(){
		return $this->town;
	}
}