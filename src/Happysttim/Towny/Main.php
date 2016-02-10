<?PHP

namespace Happysttim\Towny;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\Player;

use Happysttim\Towny\task\KeepTask;
use Happysttim\Towny\task\TownyTaxTask;
use Happysttim\Towny\task\PositionTask;

class Main extends PluginBase{
	
	private $config,$configData;
	private $town;
	private $money;
	private $scout=[];
	private $accept=[];
	
	private static $instance=null;
	
	private $taxSchedule,$keepSchedule;
	
	const PREFIX=Color::GREEN."[Towny]";
	
	public function onLoad(){
		self::$instance=$this;
	}
	/**
	* @return Main
	*/
	public static function getInstance(){
		return self::$instance;
	}
	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->config=new Config($this->getDataFolder()."townys.yml",Config::YAML,[
		"Town Level"=>"tw",
		"Max Count"=>50,
		"Max Area"=>20,
		"Max Villiger"=>30,
		"Base KeepMoney"=>500,
		"Base Scout"=>50,
		"Create Town Money"=>50000,
		"Tax Time"=>1,
		"KeepMoney Time"=>2,
		"IsCreate"=>true,
		"Town"=>[]
		]);
		if(!$this->getServer()->getPluginManager()->getPlugin("EconomyAPI")){
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$this->money=\onebone\economyapi\EconomyAPI::getInstance();
		$this->town=new Towny($this,$this->config->getAll());
		new TownyEvent($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new PositionTask($this),20);
		$this->restartTax();
		$this->restartKeepMoney();
	}
	public function onDisable(){
		$this->town->save();
		$this->getServer()->getScheduler()->cancelTask($this->taxSchedule->getTaskId());
		$this->getServer()->getScheduler()->cancelTask($this->keepSchedule->getTaskId());
	}
	public function onCommand(CommandSender $sender,Command $command,$label,array $args){
		if(!$sender instanceof Player){
			$sender->sendMessage(self::PREFIX.Color::RED." 당신은 플레이어가 아닙니다!");
			return;
		}
		switch($command->getName()){
			case "마을설정":
			if($sender->hasPermission("towny.command")){
				if(!isset($args[0])){
					$this->getOpHelper($sender);
					return;
				}
				if(is_numeric($args[0])){
					$this->getOpHelper($sender,$args[0]);
					return;
				}
				switch($args[0]){
					case "최대갯수":
					if(!isset($args[1]) or !is_numeric($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을설정 최대갯수 <갯수>");
						return;
					}
					$this->town->setMaxCount($args[1]);
					$sender->sendMessage(self::PREFIX." 설정완료");
					break;
					case "최대영역":
					if(!isset($args[1]) or !is_numeric($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을설정 최대영역 <갯수>");
						return;
					}
					$this->town->setMaxArea($args[1]);
					$sender->sendMessage(self::PREFIX." 설정완료");
					break;
					case "최대주민":
					if(!isset($args[1]) or !is_numeric($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을설정 최대주민 <갯수>");
						return;
					}
					$this->town->setMaxVilliger($args[1]);
					$sender->sendMessage(self::PREFIX." 설정완료");
					break;
					case "기초유지비":
					if(!isset($args[1]) or !is_numeric($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을설정 기초유지비 <돈>");
						return;
					}
					$this->town->setBaseKeepMoney($args[1]);
					$sender->sendMessage(self::PREFIX." 설정완료");
					break;
					case "기초영입비":
					if(!isset($args[1]) or !is_numeric($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED."/마을설정 기초영입비 <돈>");
						return;
					}
					$this->town->setBaseScout($args[1]);
					$sender->sendMessage(self::PREFIX." 설정완료");
					break;
					case "생성비":
					if(!isset($args[1]) or !is_numeric($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을설정 생성비 <돈>");
						return;
					}
					$this->town->setCreateTown($args[1]);
					$sender->sendMessage(self::PREFIX." 설정완료");
					break;
					case "세금시간":
					if(!isset($args[1]) or !is_numeric($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을설정 세금시간 <시간>");
						return;
					}
					$this->town->setTaxTime($args[1]);
					$this->restartTax();
					$sender->sendMessage(self::PREFIX." 설정완료");
					break;
					case "유지비시간":
					if(!isset($args[1]) or !is_numeric($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을설정 유지비시간 <시간>");
						return;
					}
					$this->town->setKeepMoneyTime($args[1]);
					$this->restartKeepMoney();
					$sender->sendMessage(self::PREFIX." 설정완료");
					break;
					case "생성여부":
					$this->town->changeCreate();
					$sender->sendMessage(self::PREFIX." 설정완료");
					break;
					case "삭제":
					if(!isset($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을설정 삭제 <마을이름>");
						return;
					}
					if(!$this->town->isTown($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." 존재하지 않는 마을입니다");
						return;
					}
					$this->town->removeTown($args[1]);
					$this->getServer()->broadcastMessage(self::PREFIX.Color::GOLD." {$sender->getName()} 님이 {$args[1]} 마을을 삭제하였습니다");
					break;
					case "영역티켓":
					if(!isset($args[1]) or !is_numeric($args[2])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을설정 영역티켓 <마을이름> <수량>");
						return;
					}
					if(!$this->town->isTown($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." 존재하지 않는 마을입니다");
						return;
					}
					$this->town->setAreaTicket($args[1],$args[2]);
					$sender->sendMessage(self::PREFIX." {$args[1]} 마을에 영역티켓을 설정하였습니다");
					$this->town->broadcastMessage($town,self::PREFIX." {$sender->getName()} 님이 이 마을에서 영역티켓 {$args[1]} 개로 설정하였습니다");
					break;
					case "리셋":
					$this->town->reset();
					$this->getServer()->broadcastMessage(self::PREFIX.Color::RED." {$sender->getName()} 님에 의하여 마을데이터가 리셋되었습니다");
					break;
					case "자금":
					if(!isset($args[1]) or !isset($args[2]) or !isset($args[3]) or !is_numeric($args[3])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을설정 자금 <마을이름> <인출|입금> <금액>");
						return;
					}
					if(!$this->town->isTown($args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." 존재하지 않는 마을 입니다");
						return;
					}
					$args[3]=floor($args[3]);
					switch($args[2]){
						case "인출": 
						if($args[3] < 1){
							$sender->sendMessage(self::PREFIX.Color::RED." 인출 금액이 너무 적습니다!");
						    return;
						}
						$money=$this->town->getKeepMoney($args[1]);
						if($args[3] > $money){
							$sender->sendMessage(self::PREFIX.Color::RED." 인출 금액이 너무 큽니다!");
						    return;
						}
						$this->town->delKeepMoney($args[1],$args[3]);
						$this->town->broadcastMessage($args[3],self::PREFIX." 관리자가 마을에서 {$args[3]} 만큼의 돈을 인출해갔습니다");
						$sender->sendMessage(self::PREFIX." 인출했습니다");
						break;
						case "입금":
						if($args[3] < 1){
							$sender->sendMessage(self::PREFIX.Color::RED." 인출 금액이 너무 적습니다!");
						    return;
						}
						$this->town->addKeepMoney($args[1],$args[3]);
						$this->town->broadcastMessage($args[3],self::PREFIX." 관리자가 마을에서 {$args[3]} 만큼의 돈을 입금해갔습니다");
						$sender->sendMessage(self::PREFIX." 입금했습니다");
						break;
						default:
						$sender->sendMessage(self::PREFIX.Color::RED." /마을설정 자금 <마을이름> <인출|입금> <금액>");
						return;
						break;
					}
					break;
					default:
					$this->getOpHelper($sender);
					break;
				}
			}
			break;
			case "마을관리":
			if(!$this->town->isOwner($sender->getName()) and !$this->town->isSubOwner($sender->getName())){
				$sender->sendMessage(self::PREFIX.Color::RED." 당신은 마을을 관리할수가 없습니다");
				return;
			}
			if(!isset($args[0])){
				$this->getOwnerHelper($sender);
				return;
			}
			if(is_numeric($args[0])){
				$this->getOwnerHelper($sender,$args[0]);
				return;
			}
			switch($args[0]){
 		        case "부촌장":
				if(!$this->town->isOwner($sender->getName())){
					$sender->sendMessage(self::PREFIX.Color::RED." 당신은 촌장이 아닙니다!");
					return;
				}
				if(!isset($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 부촌장 <플레이어|없음>");
					return;
				}
				$town=$this->town->getJoinTown($sender->getName());
				switch($args[1]){
					case "없음":
					$this->town->setSubOwner($town,null);
					$sender->sendMessage(self::PREFIX.Color::GOLD." 부촌장을 받지 않도록 했습니다!");
					break;
					default:
				    if(!$this->town->isMember($town,$args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." 그 플레이어는 멤버목록에 없습니다!");
						return;
					}
					$this->town->setSubOwner($town,$args[1]);
					$this->town->broadcastMessage($town,Color::GREEN."{$town} {$sender->getName()} 님에 의하여 부촌장을 {$args[1]} 님으로 선정하였습니다");
					break;
				}
				break;
				case "물려주기":
				if(!$this->town->isOwner($sender->getName())){
					$sender->sendMessage(self::PREFIX.Color::RED." 당신은 촌장이 아닙니다!");
					return;
				}
				if(!isset($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 물려주기 <플레이어>");
					return;
				}
				$town=$this->town->getJoinTown($sender->getName());
				if(!$this->town->isMember($town,$args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." 그 플레이어는 멤버목록에 없습니다!");
					return;
				}
				$this->town->setOwner($town,$args[1]);
				$this->town->broadcastMessage($town,Color::YELLOW."{$town} {$sender->getName()} 님이 촌장 자리를 {$args[1]} 님에게 물려주었습니다");
				break;
				case "세금":
				if(!isset($args[1]) or !is_numeric($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 세금 <돈>");
					return;
				}
				$args[1]=floor($args[1]);
				if($args[1] < 0){
					$sender->sendMessage(self::PREFIX.Color::RED." 세금은 0원 이상이어야 합니다");
					return;
				}
				$town=$this->town->getJoinTown($sender->getName());
				$this->town->setTax($town,$args[1]);
				$sender->sendMessage(self::PREFIX.Color::AQUA." 세금을 설정 하였습니다");
				$this->town->broadcastMessage($town,self::PREFIX.Color::GREEN." {$sender->getName()} 님이 세금을 {$args[1]} 원으로 설정하였습니다");
				break;
				case "마을이름":
				if(!$this->town->isOwner($sender->getName())){
					$sender->sendMessage(self::PREFIX.Color::RED." 당신은 촌장이 아닙니다!");
					return;
				}
				if(!isset($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 마을이름 <마을이름>");
					return;
				}
				if($this->town->isTown($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." 그 이름은 이미 존재합니다");
					return;
				}
				$town=$this->town->getJoinTown($sender->getName());
				$temp=$this->town->getTown($town);
				$this->town->removeTown($town);
				$this->town->copyTown($args[1],$temp);
				$this->town->broadcastMessage($args[1],Color::YELLOW."{$town} {$sender->getName()} 님이 마을 이름을 {$args[1]} 로 바꾸었습니다");
				break;
				case "가입방식":
				if(!$this->town->isOwner($sender->getName())){
					$sender->sendMessage(self::PREFIX.Color::RED." 당신은 촌장이 아닙니다!");
					return;
				}
				if(!isset($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 가입방식 <자유|초대>");
					return;
				}
				$town=$this->town->getJoinTown($sender->getName());
				switch($args[1]){
					case "자유":
					$this->town->setType($town,"Free");
					$sender->sendMessage(self::PREFIX.Color::YELLOW." 마을 가입방식을 자유가입제로 변경되었습니다");
					break;
					case "초대":
					$this->town->setType($town,"Visit");
					$sender->sendMessage(self::PREFIX.Color::YELLOW." 마을 가입방식을 초대가입제로 변경되었습니다");
					break;
					default:
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 가입방식 <자유|초대>");
					break;
				}
				break;
				case "토글":
				if(!isset($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 토글 <토글이름|정보>");
					return;
				}
		        $town=$this->town->getJoinTown($sender->getName());
				switch($args[1]){
					case "블럭설치":
					$this->town->changeToggle($town,"ToggleBlockPlace");
					$sender->sendMessage(Color::GREEN."[{$town}] 블럭설치를 ".($this->town->getToggle($town,"ToggleBlockPlace") ? "ON" : "OFF")." 하였습니다");
					break;
					case "블럭부수기":
					$this->town->changeToggle($town,"ToggleBlockBreak");
					$sender->sendMessage(Color::GREEN."[{$town}] 블럭부수기를 ".($this->town->getToggle($town,"ToggleBlockBreak") ? "ON" : "OFF")." 하였습니다");
					break;
					case "터치":
					$this->town->changeToggle($town,"ToggleTouch");
					$sender->sendMessage(Color::GREEN."[{$town}] 터치를 ".($this->town->getToggle($town,"ToggleTouch") ? "ON" : "OFF")." 하였습니다");
					break;
					case "폭발":
					$this->town->changeToggle($town,"ToggleExplode");
					$sender->sendMessage(Color::GREEN."[{$town}] 폭발을 ".($this->town->getToggle($town,"ToggleExplode") ? "ON" : "OFF")." 하였습니다");
					break;
					case "싸움":
					$this->town->changeToggle($town,"TogglePVP");
					$sender->sendMessage(Color::GREEN."[{$town}] 싸움을 ".($this->town->getToggle($town,"TogglePVP") ? "ON" : "OFF")." 하였습니다");
					break;
					case "아이템드롭":
					$this->town->changeToggle($town,"ToggleDropItem");
					$sender->sendMessage(Color::GREEN."[{$town}] 아이템드롭을 ".($this->town->getToggle($town,"ToggleDropItem") ? "ON" : "OFF")." 하였습니다");
					break;
					case "데미지":
					$this->town->changeToggle($town,"ToggleDamage");
					$sender->sendMessage(Color::GREEN."[{$town}] 데미지를 ".($this->town->getToggle($town,"ToggleDamage") ? "ON" : "OFF")." 하였습니다");
					break;
					case "정보":
					$blockB=$this->town->getToggle($town,"ToggleBlockBreak");
					$blockP=$this->town->getToggle($town,"ToggleBlockPlace");
					$touch=$this->town->getToggle($town,"ToggleTouch");
					$explode=$this->town->getToggle($town,"ToggleExplode");
					$pvp=$this->town->getToggle($town,"TogglePVP");
					$damage=$this->town->getToggle($town,"ToggleDamage");
					$drop=$this->town->getToggle($town,"ToggleDropItem");
					
					$sender->sendMessage(Color::YELLOW."-------------------- {$town} --------------------");
				    $sender->sendMessage(Color::GOLD."블럭부수기: {$blockB} , 블럭설치: {$blockP}");
				    $sender->sendMessage(Color::GOLD."터치: {$touch}");
				    $sender->sendMessage(Color::GOLD."폭발: {$explode}");
				    $sender->sendMessage(Color::GOLD."싸움: {$pvp}");
				    $sender->sendMessage(Color::GOLD."데미지: {$damage}");
					$sender->sendMessage(Color::GOLD."아이템드롭: {$drop}");
					break;
					default:
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 토글 <토글이름|정보>");
					break;
				}
				break;
				case "스폰포인트":
				$town=$this->town->getJoinTown($sender->getName());
				if(!$this->town->isMyArea($sender->getX() >> 4 , $sender->getZ() >> 4 , $sender->getName())){
					$sender->sendMessage(self::PREFIX.Color::RED." 자신의 마을 안에서 명령어를 실행해주세요!");
					return;
				}
				$this->town->setSpawnPoint($sender,$town);
				$sender->sendMessage(self::PREFIX.Color::GREEN." 스폰포인트를 설정하였습니다");
				break;
				case "자금":
				if(!isset($args[1]) or !isset($args[2]) or !is_numeric($args[2])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 자금 <인출|입금> <돈>");
					return;
				}
				$town=$this->town->getJoinTown($sender->getName());
				switch($args[1]){
					case "인출":
					$keep=$this->town->getKeepMoney($town);
					$args[2]=floor($args[2]);
					if($keep < $args[2]){
						$sender->sendMessage(self::PREFIX.Color::RED." 인출 하려는 돈이 현재 자금보다 더 높습니다!");
						return;
					}
					$this->town->delKeepMoney($town,$args[2]);
					$this->money->addMoney($sender,$args[2]);
					$sender->sendMessage(self::PREFIX.Color::GOLD." 성공적으로 인출하였습니다 남은 잔액: {$this->town->getKeepMoney($town)}");
					break;
					case "입금":
					$mymoney=$this->money->myMoney($sender);
					$args[2]=floor($args[2]);
					if($mymoney < $args[2]){
						$sender->sendMessage(self::PREFIX.Color::RED." 입금 하려는 돈이 현재 자금보다 더 높습니다!");
						return;
					}
					$this->town->addKeepMoney($town,$args[2]);
					$this->money->reduceMoney($sender,$args[2]);
		            $sender->sendMessage(self::PREFIX.Color::GOLD." 성공적으로 입금하였습니다 남은 내 잔액: {$this->money->myMoney($sender)}");
					break;
					default:
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 자금 <인출|입금> <돈>");
					break;
				}
				break;
				case "공지":
				if(count($args) < 2){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 공지 <공지>");
					return;
				}
				$town=$this->town->getJoinTown($sender->getName());
				array_shift($args);
				$msg=implode(" ",$args);
				$this->town->setNotice($town,$msg);
				$sender->sendMessage(self::PREFIX.Color::YELLOW." 성공적으로 공지를 넣었습니다");
				break;
				case "워프":
				if(!isset($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 워프 <추가|제거|리셋> - 마을의 워프포인트를 설정합니다");
					return;
				}
				if($sender->getLevel()->getFolderName()!=$this->town->getLevelName()){
					$sender->sendMessage(self::PREFIX.Color::RED." 마을 전용 월드에서 명령어를 이용해주세요!");
					return;
				}
				$town=$this->town->getJoinTown($sender->getName());
				switch($args[1]){
					case "추가":
					if(!isset($args[2])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 워프 추가 <이름>");
						return;
					}
					if(!$this->town->isMyArea($sender->getX() >> 4 , $sender->getZ() >> 4 , $sender->getName())){
					    $sender->sendMessage(self::PREFIX.Color::RED." 자신의 마을 내에서 워프를 추가해주세요!");
						return;
					}
					$this->town->addWarp($sender,$town,$args[2]);
					$sender->sendMessage(self::PREFIX.Color::GOLD." 워프 포인트를 추가하였습니다 이름: {$args[2]}");
					break;
					case "제거":
					if(!isset($args[2])){
						$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 워프 제거 <이름>");
						return;
					}
					$town=$this->town->getJoinTown($sender->getName());
					if(!$this->town->isWarp($town,$args[2])){
						$sender->sendMessage(self::PREFIX.Color::RED." 존재하지 않는 이름 입니다");
						return;
					}
					$this->town->delWarp($town,$args[2]);
					$sender->sendMessage(self::PREFIX.Color::GOLD." 워프 포인트를 제거하였습니다");
					break;
					case "리셋":
					$town=$this->town->getJoinTown($sender->getName());
					$this->town->resetWarp($town);
					$sender->sendMessage(self::PREFIX.Color::GOLD." 워프 포인트를 리셋 하였습니다");
					break;
					default:
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 워프 <추가|제거|리셋> - 마을의 워프포인트를 설정합니다");
					break;
				}
				break;
				case "멤버":
				if(!isset($args[1]) or !isset($args[2])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 멤버 <초대|강퇴> <플레이어>");
					return;
				}
				$town=$this->town->getJoinTown($sender->getName());
				switch($args[1]){
					case "초대":
					if($this->town->getType($town)!="Visit"){
						$sender->sendMessage(self::PREFIX.Color::RED." 가입 방식이 초대가입제가 아닙니다!");
						return;
					}
					if(!($player=$this->getServer()->getPlayerExact($args[2])) instanceof Player){
						$sender->sendMessage(self::PREFIX.Color::RED." 그 플레이어는 현재 오프라인 입니다!");
						return;
					}
					if($this->town->inTownPlayer($player->getName())){
						$sender->sendMessage(self::PREFIX.Color::RED." 그 플레이어는 이미 다른 마을에 가입되어 있습니다!");
						return;
					}
					if(isset($this->scout[$sender->getName()])){
						$sender->sendMessage(self::PREFIX.Color::RED." 당신은 이미 영입 준비중 입니다");
						return;
					}
					if(count($this->town->getMember($town)) >= $this->town->getMaxVilliger()){
					    $sender->sendMessage(self::PREFIX.Color::RED>" 그 마을의 주민은 이미 꽉 찼습니다!");
					    return;
				    }
					$scout=$this->town->getBaseScout();
					$count=count($this->town->getMember($town));
					$sender->sendMessage(self::PREFIX.Color::GOLD." 정말로 그 플레이어를 영입할건가요? 영입비용은 ".($scout * $count)." 원 입니다");
					$sender->sendMessage(self::PREFIX.Color::GOLD." 영입 할거면 /영입 , 안할거면 /취소 명령어를 입력해주세요");
					$this->scout[$sender->getName()]=[$town,($scout*$count),$player];
					break;
					case "강퇴":
					if(!$this->town->isMember($town,$args[2])){
						$sender->sendMessage(self::PREFIX.Color::RED." 그 플레이어는 당신의 마을에 속하지 않습니다!");
						return;
					}
					if($this->town->getOwner($town)==strtolower($args[2]) or $this->town->getSubOwner($town)==strtolower($args[2])){
						$sender->sendMessage(self::PREFIX.Color::RED." 마을 관리자들은 강퇴가 불가능합니다!");
						return;
					}
					$this->town->broadcastMessage($town,"[{$town}] {$sender->getName()} 님이 {$args[2]} 님을 추방하였습니다");
					$this->town->delMember($town,$args[2]);
					$ticket=$this->town->getAreaTicket($town);
					$this->town->delAreaTicket($town,2);
					$sender->sendMessage(self::PREFIX.Color::GOLD." 멤버 1명을 강퇴시킨 대가로 영역티켓 2개가 소멸하였습니다");
					break;
					default:
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 멤버 <초대|강퇴> <플레이어>");
					break;
				}
				break;
				case "영역":
				if($sender->getLevel()->getFolderName()!=$this->town->getLevelName()){
					$sender->sendMessage(self::PREFIX.Color::RED." 마을 전용 월드에서 명령어를 이용해주세요!");
					return;
				}
				if(!isset($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 영역 <추가|제거>");
					return;
				}
				$chunkX=$sender->getX() >> 4;
				$chunkZ=$sender->getZ() >> 4;
				$name=$sender->getName();
				$town=$this->town->getJoinTown($name);
				switch($args[1]){
					case "추가":
					if($this->town->checkArea($chunkX,$chunkZ,$name) or $this->town->isArea($chunkX,$chunkZ,$name)){
						$sender->sendMessage(self::PREFIX.Color::RED." 근처에 다른 마을이 있습니다! 영역 추가 실패!");
						return;
					}
					if($this->town->getAreaTicket($town) <= 0){
						$sender->sendMessage(self::PREFIX.Color::RED." 영역 티켓이 모자릅니다!");
						return;
					}
					if($this->town->getMaxArea() <= count($this->town->getAreas($town))){
						$sender->sendMessage(self::PREFIX.Color::RED." 더이상 영역을 추가할수가 없습니다!");
						return;
					}
					$this->town->addArea($sender,$town);
					$this->town->delAreaTicket($town,1);
					$sender->sendMessage(self::PREFIX.Color::GOLD." 마을의 영역을 넓혔습니다!");
					break;
					case "제거":
					if(!$this->town->isMyArea($chunkX,$chunkZ,$name)){
						$sender->sendMessage(self::PREFIX.Color::RED." 자신의 마을 내에서 영역을 제거해주세요!");
						return;
					}
					$spawn=$this->town->getSpawnPoint($town);
					$chunkX=$spawn->getX() >> 4;
					$chunkZ=$spawn->getZ() >> 4;
					$pChunkX=$sender->getX() >> 4;
					$pChunkZ=$sender->getZ() >> 4;
					if($chunkX==$pChunkX and $chunkZ==$pChunkZ){
						$sender->sendMessage(self::PREFIX.Color::RED." 삭제하려는 영역 내 스폰포인트가 있습니다!");
						$sender->sendMessage(self::PREFIX.Color::RED." 다른 청크 에 스폰포인트를 설정한뒤 영역을 삭제해주세요!");
						return;
					}
					$this->town->delArea($sender);
					$this->town->addAreaTicket($town,1);
					$sender->sendMessage(self::PREFIX.Color::GOLD." 영역을 성공적으로 삭제하였습니다!");
					break;
					default:
					$sender->sendMessage(self::PREFIX.Color::RED." /마을관리 영역 <추가|제거>");
					break;
				}
				break;
				case "삭제":
				if(!$this->town->isOwner($sender->getName())){
					$sender->sendMessage(self::PREFIX.Color::RED." 당신은 촌장이 아닙니다!");
					return;
				}
				$this->town->removeTown($this->town->getJoinTown($sender->getName()));
				$sender->sendMessage(self::PREFIX.Color::GOLD." 마을을 삭제하였습니다");
				break;
				default:
				$this->getOwnerHelper($sender);
				break;
			}
			break;
			case "마을":
			if(!isset($args[0])){
				$this->getHelper($sender);
				return;
			}
			if(is_numeric($args[0])){
				$this->getHelper($sender,$args[0]);
				return;
			}
			switch($args[0]){
				case "리스트":
				if(!isset($args[1]) or !is_numeric($args[1])){
				    $this->town->getTownList($sender);
				    return;
			    }
			    if(is_numeric($args[1])){
				    $this->town->getTownList($sender,$args[1]);
				    return;
			    }
				break;
				case "생성":
				if($sender->getLevel()->getFolderName()!=$this->town->getLevelName()){
					$sender->sendMessage(self::PREFIX.Color::RED." 마을 전용 월드에서 명령어를 이용해주세요!");
					return;
				}
				if(!isset($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED."/마을 생성 <마을이름>");
					return;
				}
				if($this->town->getJoinTown($sender->getName())){
					$sender->sendMessage(self::PREFIX.Color::RED." 이미 당신은 다른 마을에 소속되어 있습니다!");
					return;
				}
				if(!$this->town->isCreate()){
					$sender->sendMessage(self::PREFIX.Color::RED." 관리자에 의하여 마을을 세울수 없습니다!");
					return;
				}
				if($this->town->getMaxCount() <= count($this->town->getTowns())){
					$sender->sendMessage(self::PREFIX.Color::RED." 마을이 꽉 찼습니다! 마을 생성 실패!");
					return;
				}
				if($this->town->isTown($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." 이미 그 이름을 가진 마을은 생성되어 있습니다!");
					return;
				}
				if(!$this->town->getPlayerPosition($sender->getX() >> 4 , $sender->getZ() >> 4)=="야생"){
					$sender->sendMessage(self::PREFIX.Color::RED." 야생 지역에서 마을을 생성해주세요");
					return;
				}
				if($this->town->checkArea($sender->getX() >> 4 , $sender->getZ() >> 4 , $sender->getName())){
					$sender->sendMessage(self::PREFIX.Color::RED." 근처에 마을이 있습니다! 마을 생성 실패!");
					return;
				}
				$money=$this->money->myMoney($sender);
				if($this->town->getCreateTown() > $money){
					$sender->sendMessage(self::PREFIX.Color::RED." 마을을 생성하기에 돈이 모자릅니다 마을 생성비: {$this->town->getCreateTown()}");
					return;
				}
				$this->money->reduceMoney($sender,$this->town->getCreateTown());
				$this->town->createTown($sender,$args[1]);
				$sender->sendMessage(self::PREFIX.Color::GREEN." {$args[1]} 마을을 생성하였습니다");
				break;
				case "가입":
				if(!isset($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED."/마을 가입 <마을이름>");
					return;
				}
				if($this->town->getJoinTown($sender->getName())){
					$sender->sendMessage(self::PREFIX.Color::RED." 이미 당신은 다른 마을에 소속되어 있습니다!");
					return;
				}
				if(!$this->town->isTown($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." 존재하지 않는 마을입니다");
					return;
				}
				if($this->town->getType($args[1])!="Free"){
					$sender->sendMessage(self::PREFIX.Color::RED." 그 마을은 자유가입제가 아닙니다!");
					return;
				}
				if(count($this->town->getMember($args[1])) >= $this->town->getMaxVilliger()){
					$sender->sendMessage(self::PREFIX.Color::RED>" 그 마을의 주민은 이미 꽉 찼습니다!");
					return;
				}
				$scout=$this->town->getBaseScout();
				$count=count($this->town->getMember($args[1]));
			    $this->town->delKeepMoney($args[1],($scout*$count));
			    $this->town->addMember($args[1],$sender->getName());
				$this->town->broadcastMessage($args[1],self::PREFIX.Color::YELLOW." {$sender->getName()} 님이 마을에 가입하였습니다");
			    $this->town->addAreaTicket($args[1],2);
			    if($this->town->getKeepMoney($args[1]) <= 0){
				    $this->getServer()->broadcastMessage(self::PREFIX.Color::RED." {$town} 마을에 주민을 받아주다 자금이 마이너스가 되어 망했습니다");
				    $this->town->removeTown($town);
			    }
				break;
				case "정보":
				if(!isset($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을 정보 <마을이름>");
					return;
				}
				if(!$this->town->isTown($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." 존재하지 않는 마을입니다");
					return;
				}
				$owner=$this->town->getOwner($args[1]);
				$subowner=$this->town->getSubOwner($args[1]);
				$type=$this->town->getType($args[1]);
				$notice=$this->town->getNotice($args[1]);
				$member=$this->town->getMember($args[1]);
				$money=$this->town->getKeepMoney($args[1]);
				$ticket=$this->town->getAreaTicket($args[1]);
			    $payMoney=$this->town->getBaseKeepMoney()*count($this->town->getAreas($args[1]));
				$tax=$this->town->getTax($args[1]);
				$sender->sendMessage(Color::YELLOW."-------------------- {$args[1]} --------------------");
				$sender->sendMessage(Color::GOLD."촌장: {$owner} , 부촌장: ".($subowner==null ? "X" : $subowner));
				$sender->sendMessage(Color::GOLD."가입타입: {$type}");
				$sender->sendMessage(Color::GOLD."공지: {$notice}");
				$sender->sendMessage(Color::GOLD."마을자산: {$money}");
				$sender->sendMessage(Color::GOLD."마을유지비: {$payMoney}");
				$sender->sendMessage(Color::GOLD."세금: {$tax}");
				$sender->sendMessage(Color::GOLD."영역티켓: {$ticket}");
				$sender->sendMessage(Color::GOLD."추가된 영역: ".count($this->town->getAreas($args[1])));
				$sender->sendMessage(Color::GOLD."멤버: ");
				$output="";
				if(count($member) > 0){
				    foreach($member as $key){
					    $output.="{$key} ";
				    }
				    $sender->sendMessage(Color::GOLD.$output);
				}
				break;
				case "월드":
				if(!$this->getServer()->loadLevel($this->config->get("Town Level"))){
			        $sender->sendMessage(self::PREFIX.Color::RED." 마을 전용 월드가 없습니다!");
					return;
		        }
				$level=$this->getServer()->getLevelByName($this->config->get("Town Level"));
				$sender->teleport($level->getSafeSpawn());
				$sender->sendMessage(self::PREFIX.Color::GREEN." 마을 전용 월드에 워프 하였습니다");
				break;
				case "스폰":
				if(!($town=$this->town->getJoinTown($sender->getName()))){
					$sender->sendMessage(self::PREFIX.Color::RED." 당신은 다른 마을에 소속되어 있지 않습니다!");
					return;
				}
				$this->town->spawnTo($sender,$town);
				$sender->sendMessage(Color::GOLD."[{$town}] {$this->town->getNotice($town)}");
				break;
				case "워프":
				if(!($town=$this->town->getJoinTown($sender->getName()))){
					$sender->sendMessage(self::PREFIX.Color::RED." 당신은 다른 마을에 소속되어 있지 않습니다!");
					return;
				}
				if(!isset($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을 워프 <워프이름>");
					return;
				}
				switch($args[1]){
					case "리스트":
					$warps=$this->town->getWarps($town);
					$ouput="";
					$sender->sendMessage(self::PREFIX.Color::AQUA."이동가능한 마을 내 워프 목록:");
					if(count($warps) > 0){
					    foreach($warps as $key=>$value){
						    $output.="{$key} ";
					    }
						$sender->sendMessage(Color::DARK_AQUA.$output);
					}
					break;
					default:
					if(!$this->town->isWarp($town,$args[1])){
						$sender->sendMessage(self::PREFIX.Color::RED." 잘못된 이름입니다!");
						return;
					}
					$this->town->teleport($sender,$args[1]);
					$sender->sendMessage(Color::GOLD."[{$town}] {$this->town->getNotice($town)}");
					break;
				}
				break;
				case "기부":
				if(!($town=$this->town->getJoinTown($sender->getName()))){
					$sender->sendMessage(self::PREFIX.Color::RED." 당신은 다른 마을에 소속되어 있지 않습니다!");
					return;
				}
				if(!isset($args[1]) or !is_numeric($args[1])){
					$sender->sendMessage(self::PREFIX.Color::RED." /마을 기부 <돈>");
					return;
				}
				$money=$this->money->myMoney($sender);
				$args[1]=floor($args[1]);
				if($args[1] < 1){
					$sender->sendMessage(self::PREFIX.Color::RED." 기부하려는 돈이 너무 적습니다");
					return;
				}
				if($money < $args[1]){
					$sender->sendMessage(self::PREFIX.Color::RED." 당신의 자금이 입력하는 돈보다 적습니다");
					return;
				}
				$this->town->addKeepMoney($town,$args[1]);
				$this->money->reduceMoney($sender,$args[1]);
				$this->town->broadcastMessage($town,self::PREFIX.Color::YELLOW." {$sender->getName()} 님이 마을에 돈을 {$args[1]} 원 만큼 기부하였습니다");
				break;
				case "나가기":
				if(!($town=$this->town->getJoinTown($sender->getName()))){
					$sender->sendMessage(self::PREFIX.Color::RED." 당신은 다른 마을에 소속되어 있지 않습니다!");
					return;
				}
				if($this->town->getOwner($town)==strtolower($sender->getName()) or $this->town->getSubOwner($town)==strtolower($sender->getName())){
					$sender->sendMessage(self::PREFIX.Color::RED." 마을 관리자들은 못나갑니다.");
					return;
				}
				$this->town->broadcastMessage($town,self::PREFIX.Color::GOLD." {$sender->getName()} 님이 마을에서 나갔습니다");
				$this->town->delMember($town,$sender->getName());
				break;
				default:
				$this->getHelper($sender);
				break;
			}
			break;
			case "영입":
			if(!isset($this->scout[$sender->getName()])){
				$sender->sendMessage(self::PREFIX.Color::RED." 당신은 영입준비를 하지 않았습니다");
				return;
			}
			$town=$this->scout[$sender->getName()][0];
			$pay=$this->scout[$sender->getName()][1];
			$target=$this->scout[$sender->getName()][2];
			$target->sendMessage(self::PREFIX.Color::GREEN." {$town} 마을에서 가입권유가 들어와 자동으로 가입되었습니다!");
			$this->town->addMember($town,$target->getName());
			$this->town->addAreaTicket($town,2);
			$this->town->broadcastMessage($town,self::PREFIX.Color::YELLOW." {$target->getName()} 님이 마을에 가입하였습니다");
			$this->town->delKeepMoney($town,$pay);
			if($this->town->getKeepMoney($town) <= 0){
				$this->getServer()->broadcastMessage(self::PREFIX.Color::RED." {$town} 마을이 자금이 마이너스가 되어 망했습니다");
				$this->town->removeTown($town);
			}
			unset($this->scout[$sender->getName()]);
			break;
			case "취소":
			if(!isset($this->scout[$sender->getName()])){
				$sender->sendMessage(self::PREFIX.Color::RED." 당신은 영입준비를 하지 않았습니다");
				return;
			}
			unset($this->scout[$sender->getName()]);
			$sender->sendMessage(self::PREFIX.Color::GOLD." 영입을 취소하였습니다.");
			break;
		}
		$this->town->save();
	}
	/**
	* @param array $data
	*/
	public function saveYml($data){
		$this->config->setAll($data);
		$this->config->save();
	}
	/**
	* @return Towny
	*/
	public function getTowny(){
		return $this->town;
	}
	public function restartTax(){
		if($this->taxSchedule!==null){
			$this->getServer()->getScheduler()->cancelTask($this->taxSchedule->getTaskId());
		}
		$this->taxSchedule=$this->getServer()->getScheduler()->scheduleRepeatingTask(new TownyTaxTask($this,$this->money),$this->town->getTaxTime() * 1200);
	}
	public function restartKeepMoney(){
		if($this->keepSchedule!==null){
			$this->getServer()->getScheduler()->cancelTask($this->keepSchedule->getTaskId());
		}
		$this->keepSchedule=$this->getServer()->getScheduler()->scheduleRepeatingTask(new KeepTask($this,$this->money),$this->town->getKeepMoneyTime() * 1200);
	}
	public function getOpHelper(CommandSender $sender,$page=1){
		$arr=[
		"/마을설정 최대갯수 <갯수> - 마을의 최대 갯수를 설정합니다",
		"/마을설정 최대영역 <갯수> - 마을의 최대 영역을 설정합니다",
		"/마을설정 최대주민 <갯수> - 마을의 최대 주민수용을 설정합니다",
		"/마을설정 기초유지비 <돈> - 마을의 기초 유지비를 설정합니다",
		"/마을설정 기초영입비 <돈> - 마을의 기초 주민 영입비를 설정합니다",
		"/마을설정 생성비 <돈> - 마을의 생성비를 설정합니다",
		"/마을설정 세금시간 <시간> - 마을의 세금 걷는 시간을 설정합니다",
		"/마을설정 유지비시간 <시간> - 마을의 유지비 시간을 설정합니다",
		"/마을설정 생성여부 - 마을의 생성여부를 설정합니다",
		"/마을설정 삭제 <마을이름> - 마을을 삭제합니다",
		"/마을설정 영역티켓 <마을이름> <수량> - 마을의 영역티켓을 설정합니다",
		"/마을설정 리셋 - 마을을 리셋합니다",
		];
		$chunk=array_chunk($arr,5,true);
		$count=count($chunk);
		if($page > $count) $page=$count;
		$sender->sendMessage("------- Command Helper ({$page}/{$count}) -------");
		foreach($chunk[$page-1] as $key){
			$sender->sendMessage(Color::GREEN."{$key}");
		}
	}
	public function getOwnerHelper(CommandSender $sender,$page=1){
		$arr=[
		"/마을관리 부촌장 <플레이어|없음> - 부촌장을 설정합니다",
		"/마을관리 물려주기 <플레이어> - 마을의 촌장을 물려줍니다",
		"/마을관리 마을이름 <마을이름> - 마을이름을 바꿉니다",
		"/마을관리 세금 <돈> - 세금을 설정합니다",
		"/마을관리 가입방식 <자유|초대> - 마을의 가입방식을 설정합니다",
		"/마을관리 토글 <토글이름|정보> - 마을의 토글을 설정합니다",
		"/마을관리 스폰포인트 - 마을의 스폰 포인트를 설정합니다",
		"/마을관리 자금 <인출|입금> <돈> - 마을의 자금을 관리합니다",
		"/마을관리 공지 <공지> - 마을의 공지를 설정합니다",
		"/마을관리 워프 <추가|제거|리셋> - 마을의 워프포인트를 설정합니다",
		"/마을관리 멤버 <초대|강퇴> <플레이어> - 멤버를 관리합니다",
		"/마을관리 영역 <추가|제거> - 영역을 관리합니다",
		"/마을관리 삭제 - 마을을 삭제합니다",
		"/마을관리 자금 <마을이름> <인출|입금> <액수> - 해당 마을의 자금을 관리합니다",
		"/마을관리 채팅 <온|오프> - 마을 내 채팅 시스템을 끄거나 킵니다"
		];
		$chunk=array_chunk($arr,5,true);
		$count=count($chunk);
		if($page > $count) $page=$count;
		$sender->sendMessage("------- Command Helper ({$page}/{$count}) -------");
		foreach($chunk[$page-1] as $key){
			$sender->sendMessage(Color::GREEN."{$key}");
		}
	}
	public function getHelper(CommandSender $sender,$page=1){
		$arr=[
		"/마을 리스트 <페이지> - 마을 리스트들을 봅니다",
		"/마을 생성 <마을이름> - 마을을 생성합니다",
		"/마을 가입 <마을이름> - 마을에 가입합니다",
		"/마을 정보 <마을> - 해당 마을의 정보를 봅니다",
		"/마을 스폰 - 마을 스폰으로 이동합니다",
		"/마을 워프 <워프이름|리스트> - 마을의 워프시스템을 이용합니다",
		"/마을 기부 <돈> - 마을 내 자금에 돈을 기부합니다",
		"/마을 월드 - 마을 전용 월드로 이동합니다",
		"/마을 나가기 - 마을에서 나갑니다"
		];
		$chunk=array_chunk($arr,5,true);
		$count=count($chunk);
		if($page > $count) $page=$count;
		$sender->sendMessage("------- Command Helper ({$page}/{$count}) -------");
		foreach($chunk[$page-1] as $key){
			$sender->sendMessage(Color::GREEN."{$key}");
		}
	}
}