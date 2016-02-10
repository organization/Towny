<?PHP

namespace Happysttim\Towny;

use pocketmine\plugin\Plugin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\inventory\InventoryPickupItemEvent;

use Happysttim\Towny\Towny;

class TownyEvent implements Listener{
	
	private $plugin,$town;
	
	const PREFIX=Color::GREEN."[Towny]";
	
	public function __construct(Plugin $plugin){
		$this->plugin=$plugin;
		$this->town=Towny::getInstance();
		$this->plugin->getServer()->getPluginManager()->registerEvents($this,$this->plugin);
	}
	public function onPlayerJoin(PlayerJoinEvent $ev){
		$player=$ev->getPlayer();
		if(($town=$this->town->getJoinTown($player->getName()))){
			$this->town->broadcastMessage($town,self::PREFIX.Color::GOLD." 마을원 {$player->getName()} 님이 입장하였습니다");
		}
	}
	public function onPlayerQuit(PlayerQuitEvent $ev){
		$player=$ev->getPlayer();
		if(($town=$this->town->getJoinTown($player->getName()))){
			$this->town->broadcastMessage($town,self::PREFIX.Color::GOLD." 마을원 {$player->getName()} 님이 퇴장하셨습니다");
		}
	}
	public function onPlayerTouch(PlayerInteractEvent $ev){
		$this->onBlockEvent($ev,false,false,true);
	}
	public function onBlockPlace(BlockPlaceEvent $ev){
		$this->onBlockEvent($ev,false,true,false);
	}
	public function onBlockBreak(BlockBreakEvent $ev){
		$this->onBlockEvent($ev,true,false,false);
	}
	public function onBlockEvent($ev,$isBreak,$isPlace,$isTouch){
		$player=$ev->getPlayer();
		$block=$ev->getBlock();
		if($ev->isCancelled()) return;
		if($player->getLevel()->getFolderName()!=$this->town->getLevelName()) return;
		if($player->isOp()) return;
		$town=$this->town->getJoinTown($player->getName());
		if(!$this->town->inTownPlayer($player->getName())){
			$ev->setCancelled(true);
			return;
		}
		if(!$this->town->isMyArea($block->getX() >> 4 , $block->getZ() >> 4 , $player->getName())){
			$ev->setCancelled(true);
			return;
		}
		if($isBreak){
			if(!$this->town->getToggle($town,"ToggleBlockBreak")){
				$ev->setCancelled(true);
				$player->sendMessage(self::PREFIX.Color::RED." 마을 관리인에 의하여 블럭을 못 부숩니다");
				return;
			}
		}
		if($isPlace){
			if(!$this->town->getToggle($town,"ToggleBlockPlace")){
				$ev->setCancelled(true);
				$player->sendMessage(self::PREFIX.Color::RED." 마을 관리인에 의하여 블럭을 못 설치합니다");
				return;
			}
		}
		if($isTouch){
			if(!$this->town->getToggle($town,"ToggleTouch")){
				$ev->setCancelled(true);
				$player->sendMessage(self::PREFIX.Color::RED." 마을 관리인에 의하여 블럭을 터치 못합니다");
				return;
			}
		}
	}
	public function onDamage(EntityDamageEvent $ev){
		$entity=$ev->getEntity();
		if($ev->isCancelled()) return;
		if($entity instanceof Player){
			$town=$this->town->getJoinTown($entity->getName());
		    if($entity->getLevel()->getFolderName()!=$this->town->getLevelName()) return;
			if(!$this->town->inTownPlayer($entity->getName())) return;
			if(!$this->town->isMyArea($entity->x >> 4 , $entity->z >> 4,$entity->getName())) return;
			if($entity->isOp()) return;
			if($ev instanceof EntityDamageByEntityEvent){
				$damager=$ev->getDamager();
				if($damager instanceof Player){
					if(!$this->town->getToggle($town,"TogglePVP")){
				        $ev->setCancelled(true);
						$damager->sendMessage(self::PREFIX.Color::RED." 마을 관리인에 의하여 PVP를 못합니다");
			        }
				}
			}
		}
	}
	public function onExplosion(ExplosionPrimeEvent $ev){
		if($ev->isCancelled()) return;
		$entity=$ev->getEntity();
		if($entity->getLevel()->getFolderName()!=$this->town->getLevelName()) return;
		if(!$this->town->isArea($entity->x >> 4 , $entity->z >> 4)) return;
		foreach($this->town->getTowns() as $key=>$value){
			if(!$this->town->getToggle($key,"ToggleExplode")){
				$ev->setCancelled(true);
			}
		}
	}
	public function onDrop(PlayerDropItemEvent $ev){
		$player=$ev->getPlayer();
		$town=$this->town->getJoinTown($player->getName());
		if($ev->isCancelled()) return;
		if($player->getLevel()->getFolderName()!=$this->town->getLevelName()) return;
		if(!$this->town->inTownPlayer($player->getName())) return;
		if($player->isOp()) return;
		if(!$this->town->isMyArea((int)$player->x >> 4 , (int)$player->z >> 4 , (int)$player->getName())){
			$player->sendMessage(self::PREFIX.Color::RED." 소속되어 있는 마을에서 행동해주세요!");
			$ev->setCancelled(true);
			return;
		}
		if(!$this->town->getToggle($town,"ToggleDropItem")){
			$ev->setCancelled(true);
			$player->sendMessage(self::PREFIX.Color::RED." 마을 관리인에 의하여 아이템을 버리지 못합니다");
		}
	}
}