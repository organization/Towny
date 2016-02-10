<?PHP

namespace Happysttim\Towny\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\event\Cancellable;

use Happysttim\Towny\Main;

class TownyEvent extends PluginEvent implements Cancellable {
	
	private $owner;
	
	public function __construct(Main $plugin,$owner){
		parent::__construct($plugin);
		$this->owner=$owner;
	}
	public function getOwner(){
		return $this->owner;
	}
}