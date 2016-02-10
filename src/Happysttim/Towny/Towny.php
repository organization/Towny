<?PHP

namespace Happysttim\Towny;

use pocketmine\plugin\Plugin;
use pocketmine\math\Vector3;
use pocketmine\level\Location;
use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;

use Happysttim\Towny\event\player\PlayerJoinTownyMemberEvent;
use Happysttim\Towny\event\player\PlayerQuitTownyMemberEvent;
use Happysttim\Towny\event\towny\TownyCreateEvent;
use Happysttim\Towny\event\towny\TownyRemoveEvent;
use Happysttim\Towny\event\towny\AddAreaEvent;
use Happysttim\Towny\event\towny\DelAreaEvent;
use Happysttim\Towny\Main;

class Towny implements Listener{
	
	private $configData=null;
	private $plugin=null;
	static $instance=null;
		
	public function __construct(Plugin $plugin,$configData){
		self::$instance=$this;
		$this->plugin=$plugin;
		$this->configData=$configData;
		$this->plugin->getServer()->getPluginManager()->registerEvents($this,$this->plugin);
	}
	public static function getInstance(){
		return self::$instance;
	}
	public function getMaxCount(){
		return $this->configData["Max Count"];
	}
	public function getMaxArea(){
		return $this->configData["Max Area"];
	}
	public function getMaxVilliger(){
		return $this->configData["Max Villiger"];
	}
	public function getBaseKeepMoney(){
		return $this->configData["Base KeepMoney"];
	}
	public function getBaseScout(){
		return $this->configData["Base Scout"];
	}
	public function getCreateTown(){
		return $this->configData["Create Town Money"];
	}
	public function getLevelName(){
		return $this->configData["Town Level"];
	}
	public function getTaxTime(){
		return $this->configData["Tax Time"];
	}
	public function getKeepMoneyTime(){
		return $this->configData["KeepMoney Time"];
	}
	public function isCreate(){
		return (bool)$this->configData["IsCreate"];
	}
	public function getTowns(){
		return $this->configData["Town"];
	}
	public function setMaxCount($count){
		$this->configData["Max Count"]=$count;
	}
	public function setMaxArea($count){
		$this->configData["Max Area"]=$count;
	}
	public function setMaxVilliger($count){
		$this->configData["Max Villiger"]=$count;
	}
	public function setBaseKeepMoney($amount){
		$this->configData["Base KeepMoney"]=$amount;
	}
	public function setBaseScout($amount){
		$this->configData["Base Scout"]=$amount;
	}
	public function setCreateTown($amount){
		$this->configData["Create Town Money"]=$amount;
	}
	public function setLevelName($name){
		$this->configData["Town Level"]=$name;
	}
	public function setTaxTime($time){
		$this->configData["Tax Time"]=$time;
	}
	public function setKeepMoneyTime($time){
		$this->configData["KeepMoney Time"]=$time;
	}
	public function changeCreate(){
		$this->configData["IsCreate"]=!$this->configData["IsCreate"];
	}
	public function getTown($town){
		return $this->configData["Town"][$town];
	}

	public function getAreaTicket($town){
		return $this->configData["Town"][$town]["Area Ticket"];
	}
	public function getOwner($town){
		return $this->configData["Town"][$town]["Owner"];
	}
	public function getTax($town){
		return $this->configData["Town"][$town]["Tax"];
	}
	public function getSubOwner($town){
		return strtolower($this->configData["Town"][$town]["SubOwner"]);
	}
	public function getType($town){
		return $this->configData["Town"][$town]["Type"];
	}
	public function getToggle($town,$toggleName){
		return (bool)$this->configData["Town"][$town][$toggleName];
	}
	public function getSpawnPoint($town){
		return new Vector3(...explode(":",$this->configData["Town"][$town]["SpawnPoint"]));
	}
	public function getNotice($town){
		return $this->configData["Town"][$town]["Notice"];
	}
	public function getWarps($town){
		return $this->configData["Town"][$town]["WarpPoint"];
	}
	public function getMember($town){
		return $this->configData["Town"][$town]["Member"];
	}
	public function getAreas($town){
		return $this->configData["Town"][$town]["Area"];
	}
	public function isOwner($owner){
		foreach($this->getTowns() as $town=>$value){
			if(strtolower($this->configData["Town"][$town]["Owner"])==strtolower($owner)) return true;
		}
		return false;
	}
	public function isSubOwner($subOwner){
		foreach($this->getTowns() as $town=>$value){
			if(strtolower($this->configData["Town"][$town]["SubOwner"])==strtolower($subOwner)) return true;
		}
		return false;
	}
	public function getJoinTown($name){
		foreach($this->configData["Town"] as $key=>$value){
			foreach($this->configData["Town"][$key]["Member"] as $names){
				if(strtolower($name)==$names) return $key;
			}
		}
		return false;
	}
	public function getPlayer($town,$name){
		if($this->isMember($town,$name)){
			return ($player=$this->plugin->getServer()->getPlayerExact($name)) instanceof Player ? $player : null;
		}
	}
	public function getKeepMoney($town){
		return $this->configData["Town"][$town]["KeepMoney"];
	}
	public function getTownList(CommandSender $sender,$page=1){
		$page=floor($page);
		if($page < 1) $page=1;
		$chunk=array_chunk($this->getTowns(),5,true);
		$count=count($chunk);
		$sender->sendMessage(Color::GREEN."--------------- 마을 리스트 ({$page}/{$count}) ---------------");
		if($count > 0){
			$num=($page-1)*5;
			foreach($chunk[$page-1] as $key=>$value){
				++$num;
				$sender->sendMessage(Color::GREEN."[{$num}] {$key} - {$value["Owner"]}");
			}
		}
	}

	public function setAreaTicket($town,$amount){
		$this->configData["Town"][$town]["Area Ticket"]=$amount;
	}

	public function addAreaTicket($town,$amount){
		$this->configData["Town"][$town]["Area Ticket"]+=$amount;
	}

	public function delAreaTicket($town,$amount){
		$this->configData["Town"][$town]["Area Ticket"]-=$amount;
	}

	public function setOwner($town,$newOwner){
		$this->configData["Town"][$town]["Owner"]=strtolower($newOwner);
	}

	public function setSubOwner($town,$newSubOwner){
		$this->configData["Town"][$town]["SubOwner"]=strtolower($newSubOwner);
	}

	public function setType($town,$type){
		$this->configData["Town"][$town]["Type"]=$type;
	}

	public function changeToggle($town,$toggleName){
		$this->configData["Town"][$town][$toggleName]=!$this->configData["Town"][$town][$toggleName];
	}

	public function setSpawnPoint(Vector3 $vec,$town){
		$this->configData["Town"][$town]["SpawnPoint"]="{$vec->getX()}:{$vec->getY()}:{$vec->getZ()}";
	}

	public function setNotice($town,$notice){
		$this->configData["Town"][$town]["Notice"]=$notice;
	}

	public function setTax($town,$amount){
		$this->configData["Town"][$town]["Tax"]=$amount;
	}

	public function addWarp(Vector3 $vec,$town,$warpName){
		$this->configData["Town"][$town]["WarpPoint"][$warpName]=(int)$vec->getX().":".(int)$vec->getY().":".(int)$vec->getZ();
	}

	public function delWarp($town,$warpName){
		unset($this->configData["Town"][$town]["WarpPoint"][$warpName]);
	}

	public function isWarp($town,$warpName){
		foreach($this->getWarps($town) as $warp=>$value){
			if($warp==$warpName) return true;
		}
		return false;
	}

	public function resetWarp($town){
		$this->configData["Town"][$town]["WarpPoint"]=[];
	}

	public function addMember($town,$member){
		if(($player=$this->plugin->getServer()->getPlayerExact($member)) instanceof Player){
			$this->plugin->getServer()->getPluginManager()->callEvent(new PlayerJoinTownyMemberEvent($this->plugin,$player,$town));
		}
		array_push($this->configData["Town"][$town]["Member"],strtolower($member));
	}

	public function delMember($town,$member){
		if(($player=$this->plugin->getServer()->getPlayerExact($member)) instanceof Player){
			$this->plugin->getServer()->getPluginManager()->callEvent(new PlayerQuitTownyMemberEvent($this->plugin,$player,$town));
		}
		unset($this->configData["Town"][$town]["Member"][array_search(strtolower($member),$this->getMember($town))]);
	}

	public function isMember($town,$member){
		foreach($this->getMember($town) as $key){
			if(strtolower($member)==$key) return true;
		}
		return false;
	}

	public function inTownPlayer($name){
		foreach($this->getTowns() as $key=>$value){
			if($this->isMember($key,$name)) return true;
		}
		return false;
	}

	public function checkArea($chunkX,$chunkZ,$owner){
		foreach($this->getTowns() as $town=>$value){
			$areas=$this->configData["Town"][$town]["Area"];
			foreach($areas as $key){
				if($chunkX > $key["Pos1"]-2 and $chunkX < $key["Pos1"]+2 and $chunkZ > $key["Pos2"]-2 and $chunkZ < $key["Pos2"]+2){
					if($this->getOwner($town) != strtolower($owner)) return true;
				}
			}
		}
		return false;
	}

	public function isArea($chunkX,$chunkZ){
		foreach($this->getTowns() as $key=>$value){
			$areas=$this->configData["Town"][$key]["Area"];
			if(count($areas) < 1) return false;
			foreach($areas as $key){
				if($chunkX==$key["Pos1"] and $chunkZ==$key["Pos2"]) return true;
			}
		}
		return false;
	}

	public function isMyArea($chunkX,$chunkZ,$name){
		$town=$this->getJoinTown($name);
		$areas=$this->configData["Town"][$town]["Area"];
		if(count($areas) < 1) return false;
		foreach($areas as $key){
			if($chunkX == $key["Pos1"] and $chunkZ == $key["Pos2"]) return true;
		}
		return false;
	}

	public function addArea(Player $owner,$town){
		$chunkX=$owner->getX() >> 4;
		$chunkZ=$owner->getZ() >> 4;
		$this->plugin->getServer()->getPluginManager()->callEvent(new AddAreaEvent($this->plugin,$owner->getName(),$chunkX,$chunkZ,$town));
		$this->configData["Town"][$town]["Area"][]=["Pos1"=>$chunkX,"Pos2"=>$chunkZ];
	}

	public function delArea(Player $owner){
		$chunkX=$owner->getX() >> 4;
		$chunkZ=$owner->getZ() >> 4;
		$town=$this->getJoinTown($owner->getName());
		if($this->isMyArea($chunkX,$chunkZ,$owner->getName())){
			foreach($this->getAreas($town) as $key){
				if($chunkX==$key["Pos1"] and $chunkZ==$key["Pos2"]){
					$this->plugin->getServer()->getPluginManager()->callEvent(new DelAreaEvent($this->plugin,$owner->getName(),$chunkX,$chunkZ,$town));
				    unset($this->configData["Town"][$town]["Area"][array_search($key,$this->configData["Town"][$town]["Area"])]);
					return;
				}
			}
		}
	}

    public function createTown(Player $owner,$townName){
		$this->plugin->getServer()->getPluginManager()->callEvent(new TownyCreateEvent($this->plugin,$owner->getName(),$townName));
		$this->configData["Town"][$townName]=
		["Owner"=>strtolower($owner->getName()),
		"SubOwner"=>null,
		"Type"=>"Visit",
		"Tax"=>10,
		"Area Ticket"=>2,
		"ToggleBlockPlace"=>true,
		"ToggleBlockBreak"=>true,
		"ToggleTouch"=>true,
		"ToggleExplode"=>true,
		"TogglePVP"=>true,
		"ToggleDropItem"=>true,
		"ToggleDamage"=>true,
		"SpawnPoint"=>(int)$owner->getX().":".(int)$owner->getY().":".(int)$owner->getZ(),
		"KeepMoney"=>3000,
		"Notice"=>"Hello {$townName}!",
		"WarpPoint"=>[],
		"Member"=>[strtolower($owner->getName())],
		"Area"=>[]];
		$this->addArea($owner,$townName);
	}

	public function copyTown($town,$temp){
		$owner=$temp["Owner"];
		$subowner=$temp["SubOwner"];
		$type=$temp["Type"];
		$tax=$temp["Tax"];
		$ticket=$temp["Area Ticket"];
		$toggleBP=(bool)$temp["ToggleBlockPlace"];
		$toggleBB=(bool)$temp["ToggleBlockBreak"];
		$toggleT=(bool)$temp["ToggleTouch"];
		$toggleE=(bool)$temp["ToggleExplode"];
		$toggleP=(bool)$temp["TogglePVP"];
		$toggleDa=(bool)$temp["ToggleDamage"];
		$toggleD=(bool)$temp["ToggleDropItem"];
		$spawn=$temp["SpawnPoint"];
		$money=$temp["KeepMoney"];
		$notice=$temp["Notice"];
		$warps=$temp["WarpPoint"];
		$member=$temp["Member"];
		$areas=$temp["Area"];
		
		$this->configData["Town"][$town]=
		["Owner"=>$owner,
		"SubOwner"=>$subowner,
		"Type"=>$type,
		"Tax"=>$tax,
		"Area Ticket"=>$ticket,
		"ToggleBlockPlace"=>$toggleBP,
		"ToggleBlockBreak"=>$toggleBB,
		"ToggleTouch"=>$toggleT,
		"ToggleExplode"=>$toggleE,
		"TogglePVP"=>$toggleP,
		"ToggleDropItem"=>$toggleD,
		"ToggleDamage"=>$toggleD,
		"SpawnPoint"=>$spawn,
		"KeepMoney"=>$money,
		"Notice"=>$notice,
		"WarpPoint"=>$warps,
		"Member"=>$member,
		"Area"=>$areas];
	}

	public function addKeepMoney($town,$amount){
		$this->configData["Town"][$town]["KeepMoney"]+=$amount;
	}

	public function delKeepMoney($town,$amount){
		$keep=$this->configData["Town"][$town]["KeepMoney"];
		if($keep < $amount) return false;
		$this->configData["Town"][$town]["KeepMoney"]-=$amount;
	}

	public function removeTown($townName){
		$this->plugin->getServer()->getPluginManager()->callEvent(new TownyRemoveEvent($this->plugin,$this->getOwner($townName),$townName));
		unset($this->configData["Town"][$townName]);
	}

	public function isTown($townName){
		return isset($this->configData["Town"][$townName]);
	}

	public function spawnTo(Player $player,$townName){
		$level=$this->plugin->getServer()->getLevelByName($this->getLevelName());
		$spawnVec=$this->getSpawnPoint($townName);
		$player->teleport(new Location((int)$spawnVec->getX(),(int)$spawnVec->getY(),(int)$spawnVec->getZ(),-1,-1,$level));
	}

	public function teleport(Player $player,$warpName){
		$town=$this->getJoinTown($player->getName());
		$level=$this->plugin->getServer()->getLevelByName($this->getLevelName());
		$warp=explode(":",$this->configData["Town"][$town]["WarpPoint"][$warpName]);
	    $player->teleport(new Location((int)$warp[0],(int)$warp[1],(int)$warp[2],-1,-1,$level));
	}

	public function getPlayerPosition($chunkX,$chunkZ){
		foreach($this->getTowns() as $key=>$value){
			foreach($this->getAreas($key) as $area){
				if($chunkX==$area["Pos1"] and $chunkZ==$area["Pos2"]){
					return $key;
				}
			}
		}
		return "야생";
	}
	public function reset(){
		$this->configData["Town"]=[];
	}

	public function broadcastMessage($town,$message){
		foreach($this->getMember($town) as $member){
			if(($player=$this->plugin->getServer()->getPlayerExact($member)) instanceof Player){
				$player->sendMessage($message);
			}
		}
	}
	/* Save Function */
	
	public function save(){
		$this->plugin->saveYml($this->configData);
	}
}