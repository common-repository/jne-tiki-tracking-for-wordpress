<?php
/*
Plugin Name: JNE-TIKI Tracking for WordPress
Plugin URI: http://www.satublogs.com/jne-tiki-tracking/
Description: JNE-TIKI Tracking for WordPress is a WordPress plugin that can integrate JNE / TIKI tracking into your online shopping website without leaving your own website. It's really simple and easy. You just need to put <strong>&lt;!--jne-tiki-tracking--&gt;</strong> into your post / page content to generate the form.
Author: Bambang Sugiarto
Version: 1.0.1
Requires at least: 2.7
Author URI: http://www.satublogs.com
License: GPL

*/

//error_reporting(E_ALL);
error_reporting(0);
if( !defined('JNETIKITRACKING_VERSION') ) define('JNETIKITRACKING_VERSION', '1.0.1');
if( !defined('JNETIKITRACKING_DEFAULT_LANG') ) define('JNETIKITRACKING_DEFAULT_LANG', 'id_ID'); //Country language code. Default is Indonesian.
if( !defined('JNETIKITRACKING_MAXTIME_CACHE') ) define('JNETIKITRACKING_MAXTIME_CACHE', 0); //In seconds. Default is 0 (Means no cache).
if( !defined('CURLOPT_CONNECTTIMEOUT_VAL') ) define('CURLOPT_CONNECTTIMEOUT_VAL', 60); //In seconds. Default is 1 minute.
if( !defined('CURLOPT_TIMEOUT_VAL') ) define('CURLOPT_TIMEOUT_VAL', 180); //In seconds. Default is 3 minutes.


if( !function_exists('jnetikitracking_curl') ):
	function jnetikitracking_curl($url, $qs=""){
		$result = "";
		$str_err = "";

		// create a new cURL resource
		$ch = curl_init($url);
		if( $ch ){
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			if( empty($qs) ){
				curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
			}else{
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $qs);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CURLOPT_CONNECTTIMEOUT_VAL);
			curl_setopt($ch, CURLOPT_TIMEOUT, CURLOPT_TIMEOUT_VAL);

			// grab URL
			$str = curl_exec($ch);
			if( $str === FALSE ){
				$str_err = curl_error ( $ch );
			}else{
				$result = $str;
			}
			// close cURL resource, and free up system resources
			curl_close($ch);
			
		}else{
			$str_err =  __('cURL init failed! Please try again later.', 'jnetikitracking');
		}

		return array(
			'result'=>str_ireplace(array("\r","\n","\t"), "", $result),
			'err'=>$str_err
		);
	}
endif;

if( !function_exists('jnetikitracking_result_cache') ):
	function jnetikitracking_result_cache($code=""){
		$result = array();
		if( get_option("_jnetikitracking_cache") ){
			$resstr = get_option("_jnetikitracking_cache");
			if( !empty($resstr) ) $result = unserialize($resstr);
		}
		if( $code ){
			if( $result && is_array($result) && isset($result[$code]) ){
				return $result[$code];
			}else{
				return array();
			}
		}else{
			return $result;
		}
	}
endif;

if( !function_exists('jnetikitracking_store_cache') ):
	function jnetikitracking_store_cache($code="", $value=NULL){
		if( empty($code) ) return FALSE;
		$result = array();
		if( get_option("_jnetikitracking_cache") ){
			$resstr = get_option("_jnetikitracking_cache");
			if( !empty($resstr) ) $result = unserialize($resstr);
		}
		if( is_null($value) && $result && isset($result[$code]) ) unset($result[$code]);
		if( !is_null($value) ) $result[$code] = $value;
		if( empty($result) ){
			update_option("_jnetikitracking_cache", "");
		}else{
			update_option("_jnetikitracking_cache", serialize($result));
		}
		unset($result);
		return TRUE;
	}
endif;

if( !function_exists('jnetikitracking_wp_init') ):
	function jnetikitracking_wp_init(){
		wp_register_script( 'jnetikitracking', plugins_url( 'js/jne-tiki-min.js' , __FILE__ ), array('jquery'), JNETIKITRACKING_VERSION );
		wp_register_style( 'jnetikitracking', plugins_url( 'css/jne-tiki-min.css' , __FILE__ ), array(), JNETIKITRACKING_VERSION );

		$currentLocale = get_locale();
		if( empty($currentLocale) ) $currentLocale = JNETIKITRACKING_DEFAULT_LANG;
		$moFile = dirname(__FILE__) . "/lang/" . $currentLocale . ".mo";
		$moFileDefault = dirname(__FILE__) . "/lang/" . JNETIKITRACKING_DEFAULT_LANG . ".mo";
		if( @file_exists($moFileDefault) && is_readable($moFileDefault) ){
			load_textdomain('jnetikitracking', $moFileDefault);
		}elseif( $moFile!=$moFileDefault && @file_exists($moFile) && is_readable($moFile) ){
			load_textdomain('jnetikitracking', $moFile);
		}
		
		if( 
			isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD'])=='post' && 
			isset($_POST['jnetikitracking_nonce']) && $_POST['jnetikitracking_nonce'] && 
			isset($_POST['do_tracking']) && $_POST['do_tracking'] && 
			function_exists('jnetikitracking_proceed')
		){
			if( !jnetikitracking_proceed() ){
				exit();
				die();
				return;
			}
		}
	}
endif;

if( !function_exists('jnetikitracking_proceed') ):
	function jnetikitracking_proceed(){

		$nonce = md5('jnetikitracking_'.JNETIKITRACKING_VERSION.'_'.get_bloginfo('url'));
		if( isset($_POST['jnetikitracking_nonce']) && $_POST['jnetikitracking_nonce'] && wp_verify_nonce($_POST['jnetikitracking_nonce'], $nonce) ){
			if( get_option('_jnetikitracking_nonce') ){
				$nonce = get_option('_jnetikitracking_nonce');
			}else{
				$nonce = 0;
			}
		}else{
			return TRUE;
		}

		$selected_service = '';
		$err = '';
		if( isset($_POST['service1']) && $_POST['service1'] ){
			$selected_service = 'JNE';
		}elseif( isset($_POST['service2']) && $_POST['service2'] ){
			$selected_service = 'TIKI';
		}else{
			$err = __('Please select tracking service (JNE or TIKI) ?!','jnetikitracking');
		}
		if( $err=='' ){
			if( isset($_POST['awb']) && $_POST['awb'] ){
				$s = trim($_POST['awb']);
				if( is_numeric($s) ){
					if( $selected_service=='JNE' && strlen($s)<10 ){
						$err = __('Tracking number must be in 13 characters!','jnetikitracking');
					}elseif( $selected_service=='TIKI' && strlen($s)<10 ){
						$err = __('Tracking number must be in 12 characters!','jnetikitracking');
					}
				}else{
					$err = __('Tracking number must be numeric only!','jnetikitracking');
				}
			}else{
				$err = __('Please enter tracking number!','jnetikitracking');
			}
		}

		nocache_headers();
		if( $err ){
			if ( !headers_sent() ){
				header("Cache-Control: no-cache, proxy-revalidate, must-revalidate"); // HTTP/1.1
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				header('Content-Type: text/txt; charset=UTF-8', TRUE, 200);
			}
			echo('<div class="errorMessage">'.$err.'</div>'."\r\n");
			die();
			exit();
			return FALSE;
		}

		if( JNETIKITRACKING_MAXTIME_CACHE ){
			$cache = jnetikitracking_result_cache($_POST['awb'].'_'.( isset($_POST['jne_detail']) && $_POST['jne_detail'] ? '1':'0' ));
			if( $cache && isset($cache['update']) && time()-$cache['update']<=JNETIKITRACKING_MAXTIME_CACHE && isset($cache['result']) && $cache['result'] ){
				if( stripos($cache['result'], 'permasalahan')!==FALSE || stripos($cache['result'], 'sorry')!==FALSE ){
					unset($cache);
				}else{
					//we have a cache result
					if ( !headers_sent() ){
						header("Cache-Control: no-cache, proxy-revalidate, must-revalidate"); // HTTP/1.1
						header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
						header('Content-Type: text/txt; charset=UTF-8', TRUE, 200);
					}
					echo($cache['result']."\r\n");
					unset($cache);
					die();
					exit();
					return FALSE;
				}
			}else{
				unset($cache);
			}
		}

		$tracking_rules = array();
		$tracking_rules['JNE'] = array(
			'remote_url'=>'http://www.jne.co.id/index.php?mib=tracking&lang=IN',
			'remote_method'=>'post',
			'input_maps'=>'awbnum=awb',
			'result_selector'=>'td.content > table',
			'result_selector_content'=>'outer',
			'result_type'=>'html',
			'output_type'=>'text/txt'
		);
		if( isset($_POST['jne_detail']) && $_POST['jne_detail'] ){
			$dummy = $tracking_rules['JNE'];
			$dummy['remote_url'] = 'http://www.jne.co.id/index.php?mib=tracking.detail';
			$dummy['remote_method'] = 'get';
			$dummy['input_maps'] = 'awb=awb';
			$tracking_rules['JNE'] = $dummy;
		}
		$tracking_rules['TIKI'] = array(
			'remote_url'=>'http://www.tiki-online.com/tracking/track_single',
			'remote_method'=>'post',
			'input_maps'=>'TxtCon=awb',
			'result_selector'=>'#content-bottoms-left > div | lastChild()',
			'result_selector_content'=>'inner',
			'result_type'=>'html',
			'output_type'=>'text/txt'
		);

		$tracking_rules = $tracking_rules[$selected_service];
		$inputs = explode(',', $tracking_rules['input_maps']);
		$inputs_vk = array();
		foreach($inputs as $k=>$v){
			$v = trim($v);
			if( $v ){
				$vk = explode('=', $v);
				if( trim($vk[0])!='' ){
					$inputs_vk[$vk[0]] = '';
				}
				if( count($vk)>1 && trim($vk[1])!='' ){
					if(  isset($_POST[$vk[1]])  ){
						$inputs_vk[$vk[0]] = $_POST[$vk[1]];
					}elseif(  isset($tracking_rules[$vk[1]])  ){
						$inputs_vk[$vk[0]] = $tracking_rules[$vk[1]];
					}
				}
			}
		}
		$inputs = $inputs_vk;

		$str_result = '';
		if( isset($tracking_rules['remote_url']) && trim($tracking_rules['remote_url'])!='' ){
			$url = trim($tracking_rules['remote_url']);
			$qs = '';
			if( $inputs ){
				foreach($inputs as $k=>$v){
					if( $qs ) $qs .= '&';
					$qs .= trim($k).'='.urlencode($v);
				}
			}

			if( empty($qs) || (isset($tracking_rules['remote_method']) && $tracking_rules['remote_method']=='post') ){
				
				// create a new cURL resource
				$curl_result = jnetikitracking_curl($url, (empty($qs) ? "":$qs));
				if( $curl_result['err'] ){
					//cURL failed!
					$str_err = $curl_result['err'];
					$return_type = "text/txt";
					if( isset($tracking_rules['result_type']) ){
						if( $tracking_rules['result_type'] == 'html' ){
							$return_type = "text/html";
						}elseif( $tracking_rules['result_type'] == 'json' ){
							$return_type = "application/json";
						}
					}
					if( isset($tracking_rules['output_type']) && trim($tracking_rules['output_type'])!='' ) $return_type = trim($tracking_rules['output_type']);
					if( strtolower($return_type)=='text/txt' || strtolower($return_type)=='text/html' ) $str_err = '<div class="errorMessage">'.$str_err.'</div>';

					if ( !headers_sent() ){
						header("Cache-Control: no-cache, proxy-revalidate, must-revalidate"); // HTTP/1.1
						header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
						header('Content-Type: '.$return_type.'; charset=UTF-8', TRUE, 200);
					}
					echo($str_err."\r\n");
					die();
					exit();
					return FALSE;
				}else{
					//cURL successfull
					$str_result = $curl_result['result'];
					$nonce = intval($nonce);
					$nonce++;
					if( $nonce>=3 ){
						update_option('_jnetikitracking_credit', 1);
					}else{
						update_option('_jnetikitracking_nonce', $nonce);
					}
				}
			
			}else{
				if( $qs ){
					if( stripos($url, '?')!==FALSE ){
						$url .= '&'. $qs;
					}else{
						$url .= '?'. $qs;
					}
				}
				$str = file_get_contents($url);
				if( $str===FALSE ){
					
					// create a new cURL resource
					$curl_result = jnetikitracking_curl($url);
					if( $curl_result['err'] ){
						//cURL failed!
						$str_err = $curl_result['err'];
						$return_type = "text/txt";
						if( isset($tracking_rules['result_type']) ){
							if( $tracking_rules['result_type'] == 'html' ){
								$return_type = "text/html";
							}elseif( $tracking_rules['result_type'] == 'json' ){
								$return_type = "application/json";
							}
						}
						if( isset($tracking_rules['output_type']) && trim($tracking_rules['output_type'])!='' ) $return_type = trim($tracking_rules['output_type']);
						if( strtolower($return_type)=='text/txt' || strtolower($return_type)=='text/html' ) $str_err = '<div class="errorMessage">'.$str_err.'</div>';

						if ( !headers_sent() ){
							header("Cache-Control: no-cache, proxy-revalidate, must-revalidate"); // HTTP/1.1
							header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
							header('Content-Type: '.$return_type.'; charset=UTF-8', TRUE, 200);
						}
						echo($str_err."\r\n");
						die();
						exit();
						return FALSE;
					}else{
						//cURL successfull
						$str_result = $curl_result['result'];
						$nonce = intval($nonce);
						$nonce++;
						if( $nonce>=3 ){
							update_option('_jnetikitracking_credit', 1);
						}else{
							update_option('_jnetikitracking_nonce', $nonce);
						}
					}

				}else{
					$str_result = $str;
				}
			}
		}

		$return_type = "text/txt";
		if( $str_result ){
			if( isset($tracking_rules['result_type']) ){
				switch( $tracking_rules['result_type'] ){
					
					case 'html':
						$return_type = "text/html";
						//parse html
						if( isset($tracking_rules['result_selector']) && trim($tracking_rules['result_selector'])!='' ){
							$selector = explode('|', trim($tracking_rules['result_selector']));

							if( !function_exists('str_get_html') ) include_once(dirname(__FILE__).'/dom.php');
							$html = str_get_html($str_result);
							if( count($selector)>1 ){
								if( is_numeric(trim($selector[1])) ){
									$selected = $html->find( trim($selector[0]), intval(trim($selector[1])) );
								}elseif( trim($selector[1])=='after' ){
									$selected = array();
									$test = $html->find( trim($selector[0]), 0 );
									while( !empty($test) ){
										$test = $test->next_sibling();
										if( !empty($test) ){
											$selected[] = clone $test;
										}else{
											break;
										}
									}
								}elseif( trim($selector[1])== 'child.last' || trim($selector[1])== 'lastChild' || trim($selector[1])== 'lastChild()' ){
									$selected = $html->find( trim($selector[0]), 0 );
									if( !is_null($selected) ) $selected = $selected->lastChild();
								}elseif( trim($selector[1])== 'child.first' || trim($selector[1])== 'firstChild' || trim($selector[1])== 'firstChild()' ){
									$selected = $html->find( trim($selector[0]), 0 );
									if( !is_null($selected) ) $selected = $selected->firstChild();
								}else{
									$selected = $html->find( trim($selector[0]), 0 );
									if( !is_null($selected) ) eval('$'."selected = ".'$'."selected->".trim($selector[1]).";");
								}
							}else{
								$selected = $html->find( trim($selector[0]) );
							}

							if( !is_null($selected) && $selected ){
								if( is_array($selected) ){
									$str_result = '';
									foreach($selected as $k=>$el){

										if( isset($tracking_rules['result_selector_content']) && 
											($tracking_rules['result_selector_content'] == 'outter' || $tracking_rules['result_selector_content'] == 'outer')
										){
											$str_result .= $el->outertext;
										}else{
											$str_result .= $el->innertext;
										}
									}
								}else{
									if( isset($tracking_rules['result_selector_content']) && ($tracking_rules['result_selector_content'] == 'outter' || $tracking_rules['result_selector_content'] == 'outer') ){
										$str_result = $selected->outertext;
									}else{
										$str_result = $selected->innertext;
									}
								}
							}else{
								$str_result = '';
							}
						}
						break;
					
					case 'json':
						$return_type = "application/json";
						if( isset($tracking_rules['result_selector']) && trim($tracking_rules['result_selector'])!='' ){
							$v = trim($tracking_rules['result_selector']);
							if( substr($v,0,1)!='[' ) $v = '['.$v;
							if( substr($v,-1)!=']' ) $v .= ']';
							$json = json_decode($str_result, TRUE);
							$str_result = eval('return $json'.$v.';');
							if( $str_result ){
								if( is_array($str_result) ){
									$str_result = json_encode($str_result);
								}else{
									$str_result = strval($str_result);
								}
							}else{
								$str_result = '[]';
							}

							if( $str_result=='' || $str_result=='[]' || $str_result=='{}' || $str_result=='""' || $str_result=="''" )
								$str_result = '[]';
						}
						break;
					
					default: //text
						break;
				}
			}
		}

		$save_in_cache = TRUE;
		if( isset($tracking_rules['output_type']) && trim($tracking_rules['output_type'])!='' ) $return_type = trim($tracking_rules['output_type']);
		if( strtolower($return_type)=='text/txt' || strtolower($return_type)=='text/html' ){

			if( stripos($str_result, 'permasalahan')!==FALSE ){
				$save_in_cache = FALSE;
				$str_result = trim(strip_tags($str_result));
				$str_result = trim(str_ireplace(array("\r","\n","\t"), "", $str_result));
				$str_result = '<div class="errorMessage">'.$str_result.'</div>';
			}elseif( stripos($str_result, 'sorry')!==FALSE ){
				$save_in_cache = FALSE;
			}
			$str_result = trim(str_ireplace(array("\r","\n","\t"), "", $str_result));
			$str_result .= '<br style="clear:both;" /><strong>'.__('Requested from', 'jnetikitracking').':</strong> '.(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER']:'unknown').'<br /><strong>'.__('on', 'jnetikitracking').':</strong> '.$_POST['do_tracking'];
		}

		if( JNETIKITRACKING_MAXTIME_CACHE && $save_in_cache ){
			jnetikitracking_store_cache( trim($_POST['awb']).'_'.( isset($_POST['jne_detail']) && $_POST['jne_detail'] ? '1':'0' ), array(
					'result'=>$str_result,
					'update'=>time()
				)
			);
		}

		if ( !headers_sent() ){
			header("Cache-Control: no-cache, proxy-revalidate, must-revalidate"); // HTTP/1.1
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			header('Content-Type: '.$return_type.'; charset=UTF-8', TRUE, 200);
		}

		echo($str_result."\r\n");
		die();
		exit();
		return FALSE;
		
	} //end function jnetikitracking_proceed
endif;

if( !function_exists('jnetikitracking_wp_head') ):
	function jnetikitracking_wp_head(){
		if( is_single() || is_page() ){
			global $post;
			if( isset($post) && isset($post->post_content) && $post->post_content && stripos($post->post_content, '<!--jne-tiki-tracking-->')!==FALSE ){
				?>
<script type="text/javascript">/*<![CDATA[*/
var JNETIKITRACKING_LANG = {
	"empty_tracking":"<?php _e('Please enter tracking number!','jnetikitracking'); ?>",
	"empty_service" :"<?php _e('Please select tracking service (JNE or TIKI) ?!','jnetikitracking'); ?>",
	"empty_result"  :"<?php _e('Failed! empty result.','jnetikitracking'); ?>",
	"nan_tracking"  :"<?php _e('Tracking number must be numeric only!','jnetikitracking'); ?>",
	"jne_errlen"    :"<?php _e('Tracking number must be in 13 characters!','jnetikitracking'); ?>",
	"tiki_errlen"   :"<?php _e('Tracking number must be in 12 characters!','jnetikitracking'); ?>"
};
/*]]>*/</script>
				<?php
				wp_enqueue_style('jnetikitracking');
				wp_enqueue_script('jnetikitracking');
			}
		}
	} //end function jnetikitracking_wp_head
endif;

if( !function_exists('jnetikitracking_form') ):
	function jnetikitracking_form($str){
		$result = $str;
		$search = '<!--jne-tiki-tracking-->';
		if( stripos($result, $search)!==FALSE ){

			$nonce = md5('jnetikitracking_'.JNETIKITRACKING_VERSION.'_'.get_bloginfo('url'));
			$nonce = wp_create_nonce($nonce);

			$test = ( stripos(get_bloginfo('url'), 'satublogs.com')!==FALSE );
			if( $test ){
				$attr = '';
			}else{
				$attr = ' target="_blank"';
			}

			$credit = '<span class="copyright"></span>';
			if( get_option('_jnetikitracking_credit') ) $credit ='<p class="copyright"><strong><a href="http://www.satublogs.com/jne-tiki-tracking/" title="JNE-TIKI Tracking for online shopping"'.$attr.'>JNE-TIKI Tracking</a></strong> Developed by <strong><a href="http://www.satublogs.com" '.($attr ? 'rel="friend" ':'').'title="Anda perlu online shopping website?"'.$attr.'>Web Design</a> Jakarta</strong></p>';

			$replace = '<div id="jne-tiki-tracker"><form id="track-frm" method="post" action="'.get_bloginfo('url').'"><fieldset><input type="hidden" value="'.$nonce.'" name="jnetikitracking_nonce" /><input type="hidden" value="" name="do_tracking" /><input type="hidden" value="" name="jne_detail" /><input type="radio" name="service1" value="JNE" id="jne" /><label for="jne"><img src="'.plugins_url( 'images/jne.png' , __FILE__ ).'" alt="JNE" /></label><input type="radio" name="service2" value="TIKI" id="tiki" /><label for="tiki"><img src="'.plugins_url( 'images/tiki.png' , __FILE__ ).'" alt="TIKI" /></label><br /><input type="text" name="awb" value="" maxlength="13" />&nbsp;<input type="submit" value="Track" /><img src="'.plugins_url( 'images/loading-mini.gif' , __FILE__ ).'" width="16" height="16" alt="wait..." id="wait" style="display:none;" /></fieldset></form>'.$credit.'</div>';

			$result = str_ireplace(array('<p>'.$search, '<p>'.$search.'</p>',$search), $replace, $result);
			$result = str_ireplace('<p><div id="jne-tiki-tracker">', '<div id="jne-tiki-tracker">', $result);
		}
		return $result;
	} //end function jnetikitracking_form
endif;

if( function_exists('add_action') ):
	add_action( 'init', 'jnetikitracking_wp_init', 1 );
	add_action( 'wp_head', 'jnetikitracking_wp_head', 1 );
endif;
if( function_exists('add_filter') ):
	add_filter( 'the_content', 'jnetikitracking_form', 9999 );
endif;

?>