<?PHP

namespace Happysttim\Towny\event\towny;

use pocketmine\Player;

use Happysttim\Towny\event\AreaEvent;
use Happysttim\Towny\Main;

class DelAreaEvent extends AreaEvent {

	private $town;
	public static $handlerList;
	
	public function __construct(Main $plugin,$owner,$chunkX,$chunkZ,$town){
		parent::__construct($plugin,$owner,$chunkX,$chunkZ);
		
		$this->town=$town;
	}
	public function getTown(){
		return $this->town;
	}
}