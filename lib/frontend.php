<?php

class frontend{
	
	public function load($template, $replacements = []){
		
		$handle = fopen("template/{$template}", "r");
		$data = fread($handle, filesize("template/{$template}"));
		fclose($handle);
		
		$data = explode("\n", $data);
		$html = "";
		
		for($i=0; $i<count($data); $i++){
			
			$html .= trim($data[$i]);
		}
		
		foreach($replacements as $key => $value){
		
			$html =
				str_replace(
					"{%{$key}%}",
					$value,
					$html
				);
		}
		
		return trim($html);
	}
	
	public function getthemeclass($raw = true){
		
		if(
			isset($_COOKIE["theme"]) &&
			$_COOKIE["theme"] == "cream"
		){
			
			$body_class = "theme-white ";
		}else{
			
			$body_class = "";
		}
		
		if(
			$raw &&
			$body_class != ""
		){
			
			return ' class="' . rtrim($body_class) . '"';
		}
		
		return $body_class;
	}
	
	public function loadheader(array $get, array $filters, string $page){
		
		echo
			$this->load("header.html", [
				"title" => trim($get["s"] . " ({$page})"),
				"description" => ucfirst($page) . ' search results for &quot;' . htmlspecialchars($get["s"]) . '&quot;',
				"index" => "no",
				"search" => htmlspecialchars($get["s"]),
				"tabs" => $this->generatehtmltabs($page, $get["s"]),
				"filters" => $this->generatehtmlfilters($filters, $get),
				"body_class" => $this->getthemeclass()
			]);
		
		if(
			preg_match(
				'/bot|wget|curl|python-requests|scrapy|feedfetcher|go-http-client|ruby|universalfeedparser|yahoo\! slurp|spider|rss/i',
				$_SERVER["HTTP_USER_AGENT"]
			)
		){
			
			// bot detected !!
			echo
				$this->drawerror(
					"Tshh, blocked!",
					'You were blocked from viewing this page. If you wish to scrape data from 4get, please consider running <a href="https://git.lolcat.ca/lolcat/4get" rel="noreferrer nofollow">your own 4get instance</a> or using <a href="/api.txt">the API</a>.',
				);
			die();
		}
	}
	
	public function drawerror($title, $error){
		
		return
			$this->load("search.html", [
				"class" => "",
				"right-left" => "",
				"right-right" => "",
				"left" =>
					'<div class="infobox">' .
						'<h1>' . htmlspecialchars($title) . '</h1>' .
						$error .
					'</div>'
			]);
	}
	
	public function drawtextresult($site, $greentext = null, $duration = null, $keywords, $tabindex = true, $customhtml = null){
		
		$payload =
			'<div class="text-result">';
		
		// add favicon, link and archive links
		$payload .= $this->drawlink($site["url"]);
		
		/*
			Draw title + description + filetype
		*/
		$payload .=
			'<a href="' . htmlspecialchars($site["url"]) . '" class="hover" rel="noreferrer nofollow"';
			
		if($tabindex === false){
			
			$payload .= ' tabindex="-1"';
		}
			
		$payload .= '>';
			
			if($site["thumb"]["url"] !== null){
				
				$payload .=
					'<div class="thumb-wrap';
				
				switch($site["thumb"]["ratio"]){
					
					case "16:9":
						$size = "landscape";
						break;
					
					case "9:16":
						$payload .= " portrait";
						$size = "portrait";
						break;
					
					case "1:1":
						$payload .= " square";
						$size = "square";
						break;
				}
				
				$payload .=
					'">' .
						'<img class="thumb" src="' . $this->htmlimage($site["thumb"]["url"], $size) . '" alt="thumb">';
				
				if($duration !== null){
					
					$payload .=
						'<div class="duration">' .
							htmlspecialchars($duration) .
						'</div>';
				}
				
				$payload .=
					'</div>';
			}
			
		$payload .=
			'<div class="title">';
		
		if(
			isset($site["type"]) &&
			$site["type"] != "web"
		){
			
			$payload .= '<div class="type">' . strtoupper($site["type"]) . '</div>';
		}
		
		$payload .=
			$this->highlighttext($keywords, $site["title"]) .
		'</div>';
		
		if($greentext !== null){
			
			$payload .=
				'<div class="greentext">' .
					htmlspecialchars($greentext) .
				'</div>';
		}
		
		if($site["description"] !== null){
			
			$payload .=
				'<div class="description">' .
					$this->highlighttext($keywords, $site["description"]) .
				'</div>';
		}
		
		$payload .= $customhtml;
		
		$payload .= '</a>';
		
		/*
			Sublinks
		*/
		if(
			isset($site["sublink"]) &&
			!empty($site["sublink"])
		){
			
			usort($site["sublink"], function($a, $b){
				
				return strlen($a["description"]) > strlen($b["description"]);
			});
			
			$payload .=
				'<div class="sublinks">' .
					'<table>';
			
			$opentr = false;
			for($i=0; $i<count($site["sublink"]); $i++){
				
				if(($i % 2) === 0){
					
					$opentr = true;
					$payload .= '<tr>';
				}else{
					
					$opentr = false;
				}
				
				$payload .=
					'<td>' .
						'<a href="' . htmlspecialchars($site["sublink"][$i]["url"]) . '" rel="noreferrer nofollow">' .
							'<div class="title">' .
								htmlspecialchars($site["sublink"][$i]["title"]) .
							'</div>';
				
				if(!empty($site["sublink"][$i]["date"])){
					
					$payload .=
						'<div class="greentext">' .
							date("jS M y @ g:ia", $site["sublink"][$i]["date"]) .
						'</div>';
				}
				
				if(!empty($site["sublink"][$i]["description"])){
					
					$payload .=
						'<div class="description">' .
							$this->highlighttext($keywords, $site["sublink"][$i]["description"]) .
						'</div>';
				}
				
				$payload .= '</a></td>';
				
				if($opentr === false){
					
					$payload .= '</tr>';
				}
			}
			
			if($opentr === true){
				
				$payload .= '<td></td></tr>';
			}
			
			$payload .= '</table></div>';
		}
		
		if(
			isset($site["table"]) &&
			!empty($site["table"])
		){
			
			$payload .= '<table class="info-table">';
			
			foreach($site["table"] as $title => $value){
				
				$payload .=
					'<tr>' .
						'<td>' . htmlspecialchars($title) . '</td>' .
						'<td>' . htmlspecialchars($value) . '</td>' .
					'</tr>';
			}
			
			$payload .= '</table>';
		}
		
		return $payload . '</div>';
	}
	
	public function highlighttext($keywords, $text){
		
		$text = htmlspecialchars($text);
		
		$keywords = explode(" ", $keywords);
		$regex = [];
		
		foreach($keywords as $word){
			
			$regex[] = "\b" . preg_quote($word, "/") . "\b";
		}
		
		$regex = "/" . implode("|", $regex) . "/i";
		
		return
			preg_replace(
				$regex,
				'<b>${0}</b>',
				$text
			);
	}
	
	function highlightcode($text){
		
		// https://www.php.net/highlight_string
		ini_set("highlight.comment", "c-comment");
		ini_set("highlight.default", "c-default");
		ini_set("highlight.html", "c-default");
		ini_set("highlight.keyword", "c-keyword");
		ini_set("highlight.string", "c-string");
		
		$text =
			trim(
				preg_replace(
					'/<\/span>$/',
					"", // remove stray ending span because of the <?php stuff
					str_replace(
						[
							'<br />',
							'&nbsp;'
						],
						[
							"\n", // replace <br> with newlines
							" " // replace html entity to space
						],
						str_replace(
							[
								// leading <?php garbage
								"<span style=\"color: c-default\">\n&lt;?php&nbsp;",
								"<code>",
								"</code>"
							],
							"",
							highlight_string("<?php " . $text, true)
						)
					)
				)
			);
		
		// replace colors
		$classes = ["c-comment", "c-default", "c-keyword", "c-string"];
		
		foreach($classes as $class){
			
			$text = str_replace('<span style="color: ' . $class . '">', '<span class="' . $class . '">', $text);
		}
		
		return $text;
	}
	
	public function drawlink($link){
		
		/*
			Add favicon
		*/
		$host = parse_url($link);
		$esc =
			explode(
				".",
				$host["host"],
				2
			);
		
		if(
			count($esc) === 2 &&
			$esc[0] == "www"
		){
			
			$esc = $esc[1];
		}else{
			
			$esc = $esc[0];
		}
		
		$esc = substr($esc, 0, 2);
		
		$urlencode = urlencode($link);
		
		$payload =
			'<div class="url">' .
				'<button class="favicon" tabindex="-1">' .
					'<img src="/favicon?s=' . htmlspecialchars($host["scheme"] . "://" . $host["host"]) . '" alt="' . htmlspecialchars($esc) . '">' .
					//'<img src="/404.php" alt="' . htmlspecialchars($esc) . '">' .
				'</button>' .
				'<div class="favicon-dropdown">';
		
		/*
			Add archive links
		*/
		if(
			$host["host"] == "boards.4chan.org" ||
			$host["host"] == "boards.4channel.org"
		){
			
			$archives = [];
			$path = explode("/", $host["path"]);
			$count = count($path);
			// /pol/thread/417568063/post-shitty-memes-if-you-want-to
			
			if($count !== 0){
				
				$isboard = true;
				
				switch($path[1]){
					
					case "con":
						break;
					
					case "q":
						$archives[] = "desuarchive.org";
						break;
						
					case "qa":
						$archives[] = "desuarchive.org";
						break;
						
					case "qb":
						$archives[] = "arch.b4k.co";
						break;
						
					case "trash":
						$archives[] = "desuarchive.org";
						break;
					
					case "a":
						$archives[] = "desuarchive.org";
						break;
					
					case "c":
						$archives[] = "desuarchive.org";
						break;
					
					case "w":
						break;
					
					case "m":
						$archives[] = "desuarchive.org";
						break;
					
					case "cgl":
						$archives[] = "desuarchive.org";
						$archives[] = "warosu.org";
						break;
					
					case "cm":
						$archives[] = "boards.fireden.net";
						break;
					
					case "f":
						$archives[] = "archive.4plebs.org";
						break;
					
					case "n":
						break;
					
					case "jp":
						$archives[] = "warosu.org";
						break;
					
					case "vt":
						$archives[] = "warosu.org";
						break;
					
					case "v":
						$archives[] = "boards.fireden.net";
						$archives[] = "arch.b4k.co";
						break;
					
					case "vg":
						$archives[] = "boards.fireden.net";
						$archives[] = "arch.b4k.co";
						break;
					
					case "vm":
						$archives[] = "arch.b4k.co";
						break;
					
					case "vmg":
						$archives[] = "arch.b4k.co";
						break;
					
					case "vp":
						$archives[] = "arch.b4k.co";
						break;
					
					case "vr":
						$archives[] = "desuarchive.org";
						$archives[] = "warosu.org";
						break;
					
					case "vrpg":
						$archives[] = "arch.b4k.co";
						break;
					
					case "vst":
						$archives[] = "arch.b4k.co";
						break;
					
					case "co":
						$archives[] = "desuarchive.org";
						break;
					
					case "g":
						$archives[] = "desuarchive.org";
						$archives[] = "arch.b4k.co";
						break;
					
					case "tv":
						$archives[] = "archive.4plebs.org";
						break;
					
					case "k":
						$archives[] = "desuarchive.org";
						break;
					
					case "o":
						$archives[] = "archive.4plebs.org";
						break;
					
					case "an":
						$archives[] = "desuarchive.org";
						break;
					
					case "tg":
						$archives[] = "desuarchive.org";
						$archives[] = "archive.4plebs.org";
						break;
					
					case "sp":
						$archives[] = "archive.4plebs.org";
						break;
					
					case "xs":
						$archives[] = "eientei.xyz";
						break;
					
					case "pw":
						break;
					
					case "sci":
						$archives[] = "boards.fireden.net";
						$archives[] = "warosu.org";
						$archives[] = "eientei.xyz";
						break;
					
					case "his":
						$archives[] = "desuarchive.org";
						break;
					
					case "int":
						$archives[] = "desuarchive.org";
						break;
					
					case "out":
						break;
					
					case "toy":
						break;
					
					case "i":
						$archives[] = "archiveofsins.com";
						$archives[] = "eientei.xyz";
						break;
					
					case "po":
						break;
					
					case "p":
						break;
					
					case "ck":
						$archives[] = "warosu.org";
						break;
					
					case "ic":
						$archives[] = "boards.fireden.net";
						$archives[] = "warosu.org";
						break;
					
					case "wg":
						break;
					
					case "lit":
						$archives[] = "warosu.org";
						break;
					
					case "mu":
						$archives[] = "desuarchive.org";
						break;
					
					case "fa":
						$archives[] = "warosu.org";
						break;
					
					case "3":
						$archives[] = "warosu.org";
						$archives[] = "eientei.xyz";
						break;
					
					case "gd":
						break;
					
					case "diy":
						$archives[] = "warosu.org";
						break;
					
					case "wsg":
						$archives[] = "desuarchive.org";
						break;
					
					case "qst":
						break;
					
					case "biz":
						$archives[] = "warosu.org";
						break;
					
					case "trv":
						$archives[] = "archive.4plebs.org";
						break;
					
					case "fit":
						$archives[] = "desuarchive.org";
						break;
					
					case "x":
						$archives[] = "archive.4plebs.org";
						break;
					
					case "adv":
						$archives[] = "archive.4plebs.org";
						break;
					
					case "lgbt":
						$archives[] = "archiveofsins.com";
						break;
					
					case "mlp":
						$archives[] = "desuarchive.org";
						$archives[] = "arch.b4k.co";
						break;
					
					case "news":
						break;
					
					case "wsr":
						break;
					
					case "vip":
						break;
					
					case "b":
						$archives[] = "thebarchive.com";
						break;
					
					case "r9k":
						$archives[] = "desuarchive.org";
						break;
					
					case "pol":
						$archives[] = "archive.4plebs.org";
						break;
					
					case "bant":
						$archives[] = "thebarchive.com";
						break;
					
					case "soc":
						$archives[] = "archiveofsins.com";
						break;
					
					case "s4s":
						$archives[] = "archive.4plebs.org";
						break;
					
					case "s":
						$archives[] = "archiveofsins.com";
						break;
					
					case "hc":
						$archives[] = "archiveofsins.com";
						break;
					
					case "hm":
						$archives[] = "archiveofsins.com";
						break;
					
					case "h":
						$archives[] = "archiveofsins.com";
						break;
					
					case "e":
						break;
					
					case "u":
						$archives[] = "archiveofsins.com";
						break;
					
					case "d":
						$archives[] = "desuarchive.org";
						break;
					
					case "y":
						$archives[] = "boards.fireden.net";
						break;
					
					case "t":
						$archives[] = "archiveofsins.com";
						break;
					
					case "hr":
						$archives[] = "archive.4plebs.org";
						break;
					
					case "gif":
						break;
					
					case "aco":
						$archives[] = "desuarchive.org";
						break;
					
					case "r":
						$archives[] = "archiveofsins.com";
						break;
					
					default:
						$isboard = false;
						break;
				}
				
				if($isboard === true){
					
					$archives[] = "archived.moe";
				}
				
				$trail = "";
				
				if(
					isset($path[2]) &&
					isset($path[3]) &&
					$path[2] == "thread"
				){
					
					$trail .= "/" . $path[1] . "/thread/" . $path[3];
				}elseif($isboard){
					
					$trail = "/" . $path[1] . "/";
				}
				
				for($i=0; $i<count($archives); $i++){
					
					$payload .=
						'<a href="https://' . $archives[$i] . $trail . '" class="list" target="_BLANK">' .
							'<img src="/favicon?s=https://' . $archives[$i] . '" alt="' . $archives[$i][0] . $archives[$i][1] . '">' .
							$archives[$i] .
						'</a>';
				}
			}
		}
		
		$payload .=
				'<a href="https://webcache.googleusercontent.com/search?q=cache:' . $urlencode . '" class="list" target="_BLANK"><img src="/favicon?s=https://google.com" alt="go">Google cache</a>' .
				'<a href="https://web.archive.org/web/' . $urlencode . '" class="list" target="_BLANK"><img src="/favicon?s=https://archive.org" alt="ar">Archive.org</a>' .
				'<a href="https://archive.is/newest/' . htmlspecialchars($link) . '" class="list" target="_BLANK"><img src="/favicon?s=https://archive.is" alt="ar">Archive.is</a>' .
				'<a href="https://ghostarchive.org/search?term=' . $urlencode . '" class="list" target="_BLANK"><img src="/favicon?s=https://ghostarchive.org" alt="gh">Ghostarchive</a>' .
				'<a href="https://www.bing.com/search?q=url%3A' . $urlencode . '" class="list" target="_BLANK"><img src="/favicon?s=https://bing.com" alt="bi">Bing cache</a>' .
				'<a href="https://megalodon.jp/?url=' . $urlencode . '" class="list" target="_BLANK"><img src="/favicon?s=https://megalodon.jp" alt="me">Megalodon</a>' .
			'</div>';
		
		/*
			Draw link
		*/
		$parts = explode("/", $link);
		$clickurl = "";
		
		// remove trailing /
		$c = count($parts) - 1;
		if($parts[$c] == ""){
			
			$parts[$c - 1] = $parts[$c - 1] . "/";
			unset($parts[$c]);
		}
		
		// merge https://site together
		$parts = [
			$parts[0] . $parts[1] . '//' . $parts[2],
			...array_slice($parts, 3, count($parts) - 1)
		];
		
		$c = count($parts);
		for($i=0; $i<$c; $i++){
			
			if($i !== 0){ $clickurl .= "/"; }
			
			$clickurl .= $parts[$i];
			
			if($i === $c - 1){
				
				$parts[$i] = rtrim($parts[$i], "/");
			}
			
			$payload .=
				'<a class="part" href="' . htmlspecialchars($clickurl) . '" rel="noreferrer nofollow" tabindex="-1">' .
					htmlspecialchars(urldecode($parts[$i])) .
				'</a>';
			
			if($i !== $c - 1){
				
				$payload .= '<span class="separator"></span>';
			}
		}
		
		return $payload . '</div>';
	}
	
	public function getscraperfilters($page){
		
		$get_scraper = null;
		
		switch($page){
			
			case "web":
				$get_scraper = isset($_COOKIE["scraper_web"]) ? $_COOKIE["scraper_web"] : null;
				break;
			
			case "images":
				$get_scraper = isset($_COOKIE["scraper_images"]) ? $_COOKIE["scraper_images"] : null;
				break;
			
			case "videos":
				$get_scraper = isset($_COOKIE["scraper_videos"]) ? $_COOKIE["scraper_videos"] : null;
				break;
			
			case "news":
				$get_scraper = isset($_COOKIE["scraper_news"]) ? $_COOKIE["scraper_news"] : null;
				break;
			
			case "music":
				$get_scraper = isset($_COOKIE["scraper_news"]) ? $_COOKIE["scraper_news"] : null;
				break;
		}
		
		if(
			isset($_GET["scraper"]) &&
			is_string($_GET["scraper"])
		){
			
			$get_scraper = $_GET["scraper"];
		}else{
			
			if(
				isset($_GET["npt"]) &&
				is_string($_GET["npt"])
			){
				
				$get_scraper = explode(".", $_GET["npt"], 2)[0];
				
				$get_scraper =
					preg_replace(
						'/[0-9]+$/',
						"",
						$get_scraper
					);
			}
		}
		
		// add search field
		$filters =
			[
				"s" => [
					"option" => "_SEARCH"
				]
			];
		
		// define default scrapers
		switch($page){
			
			case "web":
				$filters["scraper"] = [
					"display" => "Scraper",
					"option" => [
						"ddg" => "DuckDuckGo",
						"brave" => "Brave",
						"yandex" => "Yandex",
						//"google" => "Google",
						"mojeek" => "Mojeek",
						"marginalia" => "Marginalia",
						"wiby" => "wiby"
					]
				];
				break;
			
			case "images":
				$filters["scraper"] = [
					"display" => "Scraper",
					"option" => [
						"ddg" => "DuckDuckGo",
						"yandex" => "Yandex",
						"brave" => "Brave",
						"google" => "Google"
					]
				];
				break;
			
			case "videos":
				$filters["scraper"] = [
					"display" => "Scraper",
					"option" => [
						"yt" => "YouTube",
						//"fb" => "Facebook videos",
						"ddg" => "DuckDuckGo",
						"brave" => "Brave",
						"yandex" => "Yandex"
						//"google" => "Google"
					]
				];
				break;
			
			case "news":
				$filters["scraper"] = [
					"display" => "Scraper",
					"option" => [
						"ddg" => "DuckDuckGo",
						"brave" => "Brave",
						//"google" => "Google",
						"mojeek" => "Mojeek"
					]
				];
			
			case "music":
				$filters["scraper"] = [
					"display" => "Scraper",
					"option" => [
						"sc" => "SoundCloud"
					]
				];
				break;
		}
		
		// get scraper name from user input, or default out to preferred scraper
		$scraper_out = null;
		$first = true;
		
		foreach($filters["scraper"]["option"] as $scraper_name => $scraper_pretty){
			
			if($first === true){
				
				$first = $scraper_name;
			}
			
			if($scraper_name == $get_scraper){
				
				$scraper_out = $scraper_name;
			}
		}
		
		if($scraper_out === null){
			
			$scraper_out = $first;
		}
		
		switch($scraper_out){
			
			case "ddg":
				include "scraper/ddg.php";
				$lib = new ddg();
				break;
			
			case "brave":
				include "scraper/brave.php";
				$lib = new brave();
				break;
			
			case "yt";
				include "scraper/youtube.php";
				$lib = new youtube();
				break;
			
			case "yandex":
				include "scraper/yandex.php";
				$lib = new yandex();
				break;
			
			case "google":
				include "scraper/google.php";
				$lib = new google();
				break;
			/*
			case "fb":
				include "scraper/facebook.php";
				$lib = new facebook();
				break;*/
			
			case "mojeek":
				include "scraper/mojeek.php";
				$lib = new mojeek();
				break;
			
			case "marginalia":
				include "scraper/marginalia.php";
				$lib = new marginalia();
				break;
			
			case "wiby":
				include "scraper/wiby.php";
				$lib = new wiby();
				break;
			
			case "sc":
				include "scraper/sc.php";
				$lib = new sc();
				break;
		}
		
		// set scraper on $_GET
		$_GET["scraper"] = $scraper_out;
		
		// set nsfw on $_GET
		if(
			isset($_COOKIE["nsfw"]) &&
			!isset($_GET["nsfw"])
		){
			
			$_GET["nsfw"] = $_COOKIE["nsfw"];
		}
		
		return
			[
				$lib,
				array_merge_recursive(
					$filters,
					$lib->getfilters($page)
				)
			];
	}
	
	public function parsegetfilters($parameters, $whitelist){
		
		$sanitized = [];
		
		// add npt token
		if(
			isset($parameters["npt"]) &&
			is_string($parameters["npt"])
		){
			
			$sanitized["npt"] = $parameters["npt"];
		}else{
			
			$sanitized["npt"] = false;
		}
		
		// we're iterating over $whitelist, so
		// you can't polluate $sanitized with useless
		// parameters
		foreach($whitelist as $parameter => $value){
			
			if(isset($parameters[$parameter])){
				
				if(!is_string($parameters[$parameter])){
					
					$sanitized[$parameter] = null;
					continue;
				}
				
				// parameter is already set, use that value
				$sanitized[$parameter] = $parameters[$parameter];
			}else{
				
				// parameter is not set, add it
				if(is_string($value["option"])){
					
					// special field: set default value manually
					switch($value["option"]){
						
						case "_DATE":
							// no date set
							$sanitized[$parameter] = false;
							break;
						
						case "_SEARCH":
							// no search set
							$sanitized[$parameter] = "";
							break;
					}
					
				}else{
					
					// set a default value
					$sanitized[$parameter] = array_keys($value["option"])[0];
				}
			}
			
			// sanitize input
			if(is_array($value["option"])){
				if(
					!in_array(
						$sanitized[$parameter],
						$keys = array_keys($value["option"])
					)
				){
					
					$sanitized[$parameter] = $keys[0];
				}
			}else{
				
				// sanitize search & string
				switch($value["option"]){
					
					case "_DATE":
						if($sanitized[$parameter] !== false){
							
							$sanitized[$parameter] = strtotime($sanitized[$parameter]);
							if($sanitized[$parameter] <= 0){
								
								$sanitized[$parameter] = false;
							}
						}
						break;
					
					case "_SEARCH":
						
						// get search string & bang
						$sanitized[$parameter] = trim($sanitized[$parameter]);
						$sanitized["bang"] = "";
						
						if(
							strlen($sanitized[$parameter]) !== 0 &&
							$sanitized[$parameter][0] == "!"
						){
							
							$sanitized[$parameter] = explode(" ", $sanitized[$parameter], 2);
							
							$sanitized["bang"] = trim($sanitized[$parameter][0]);
							
							if(count($sanitized[$parameter]) === 2){
								
								$sanitized[$parameter] = trim($sanitized[$parameter][1]);
							}else{
								
								$sanitized[$parameter] = "";
							}
							
							$sanitized["bang"] = ltrim($sanitized["bang"], "!");
						}
						
						$sanitized[$parameter] = ltrim($sanitized[$parameter], "! \n\r\t\v\x00");
				}
			}
		}
		
		// invert dates if needed
		if(
			isset($sanitized["older"]) &&
			isset($sanitized["newer"]) &&
			$sanitized["newer"] !== false &&
			$sanitized["older"] !== false &&
			$sanitized["newer"] > $sanitized["older"]
		){
			
			// invert
			[
				$sanitized["older"],
				$sanitized["newer"]
			] = [
				$sanitized["newer"],
				$sanitized["older"]
			];
		}
		
		return $sanitized;
	}

	public function s_to_timestamp($seconds){
		
		if(is_string($seconds)){
			
			return "LIVE";
		}
		
		return ($seconds >= 60) ? ltrim(gmdate("H:i:s", $seconds), ":0") : gmdate("0:s", $seconds);
	}
	
	public function generatehtmltabs($page, $query){
		
		$html = null;
		
		foreach(["web", "images", "videos", "news", "music"] as $type){
			
			$html .= '<a href="/' . $type . '?s=' . urlencode($query);
			
			if(!empty($params)){
				
				$html .= $params;
			}
			
			$html .= '" class="tab';
			
			if($type == $page){
				
				$html .= ' selected';
			}
			
			$html .= '">' . ucfirst($type) . '</a>';
		}
		
		return $html;
	}
	
	public function generatehtmlfilters($filters, $params){
		
		$html = null;
		
		foreach($filters as $filter_name => $filter_values){
			
			if(!isset($filter_values["display"])){
				
				continue;
			}
			
			$output = true;
			$tmp =
				'<div class="filter">' .
					'<div class="title">' . htmlspecialchars($filter_values["display"]) . '</div>';
			
			if(is_array($filter_values["option"])){
				
				$tmp .= '<select name="' . $filter_name . '">';
				
				foreach($filter_values["option"] as $option_name => $option_title){
					
					$tmp .= '<option value="' . $option_name . '"';
					
					if($params[$filter_name] == $option_name){
						
						$tmp .= ' selected';
					}
					
					$tmp .= '>' . htmlspecialchars($option_title) . '</option>';
				}
				
				$tmp .= '</select>';
			}else{
				
				switch($filter_values["option"]){
					
					case "_DATE":
						$tmp .= '<input type="date" name="' . $filter_name . '"';
						
						if($params[$filter_name] !== false){
							
							$tmp .= ' value="' . date("Y-m-d", $params[$filter_name]) . '"';
						}
						
						$tmp .= '>';
						break;
					
					default:
						$output = false;
						break;
				}
			}
			
			$tmp .= '</div>';
			
			if($output === true){
				
				$html .= $tmp;
			}
		}
		
		return $html;
	}
	
	public function buildquery($gets, $ommit = false){
		
		$out = [];
		foreach($gets as $key => $value){
			
			if(
				$value == null ||
				$value == false ||
				$key == "npt" ||
				$key == "extendedsearch" ||
				$value == "any" ||
				$value == "all" ||
				(
					$ommit === true &&
					$key == "s"
				)
			){
				
				continue;
			}
			
			if(
				$key == "older" ||
				$key == "newer"
			){
				
				$value = date("Y-m-d", (int)$value);
			}
			
			$out[$key] = $value;
		}
		
		return http_build_query($out);
	}
	
	public function htmlimage($image, $format){
		
		if(
			preg_match(
				'/^data:/',
				$image
			)
		){
			
			return htmlspecialchars($image);
		}
		
		return "/proxy.php?i=" . urlencode($image) . "&s=" . $format;
	}
	
	public function htmlnextpage($gets, $npt, $page){
		
		$query = $this->buildquery($gets);
		
		return $page . "?" . $query . "&npt=" . $npt;
	}
}
