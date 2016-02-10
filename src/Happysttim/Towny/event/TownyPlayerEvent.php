<?PHP

namespace Happysttim\Towny\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\event\Cancellable;

use Happysttim\Towny\Main;

class TownyPlayerEvent extends PluginEvent implements Cancellable {
	
	private $player;
	
	public function __construct(Main $plugin,$player){
		parent::__construct($plugin);
		$this->player=$player;
	}
	public function getPlayer(){
		return $this->player;
	}
}