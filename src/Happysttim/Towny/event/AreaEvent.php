<?PHP

namespace Happysttim\Towny\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\event\Cancellable;

use Happysttim\Towny\Main;

class AreaEvent extends PluginEvent implements Cancellable {
	
	private $owner;
	private $chunkX,$chunkZ;
	
	public function __construct(Main $plugin,$owner,$chunkX,$chunkZ){
		parent::__construct($plugin);
		$this->owner=$owner;
		$this->chunkX=$chunkX;
		$this->chunkZ=$chunkZ;
	}
	public function getOwner(){
		return $this->owner;
	}
	public function getChunkX(){
		return $this->chunkX;
	}
	public function getChunkZ(){
		return $this->chunkZ();
	}
}