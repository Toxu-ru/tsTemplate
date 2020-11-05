<?php
//defined ( 'IN_TEST' ) or die ( 'Access Denied.' );
class tsTemplate {
	var $var_regexp = "\@?\\\$[a-zA-Z_][\\\$\w]*(?:\[[\w\-\.\"\'\[\]\$]+\])*";
	var $vtag_regexp = "\<\?php echo (\@?\\\$[a-zA-Z_][\\\$\w]*(?:\[[\w\-\.\"\'\[\]\$]+\])*)\;\?\>";
	var $const_regexp = "\{([\w]+)\}";
	
	/**
	 * Прочитать шаблон, записать в cache
	 * @param string $tplfile
	 *        	：исходный файл шаблона
	 * @param string $objfile
	 *        	：для cache
	 * @return string
	 */
	function complie($tplfile, $objfile) {
		// $template = " ";
		$template .= file_get_contents ( $tplfile );
		$template = $this->parse ( $template );
		mkdir ( dirname ( $objfile ) );
		$this->isWriteFile ( $objfile, $template, $mod = 'w', TRUE );
	}
	
function makedir($dir) {
	return is_dir($dir) or (makedir(dirname($dir)) and mkdir($dir, 0777));
}
function isWriteFile($file, $content, $mod = 'w', $exit = TRUE) {
	if (!@$fp = @fopen($file, $mod)) {
		if ($exit) {
			exit('File :<br>' . $file . '<br>Have no access to write!');
		} else {
			return false;
		}
	} else {
		@flock($fp, 2);
		@fwrite($fp, $content);
		@fclose($fp);
		return true;
	}
}

	/**
	 * Синтаксический анализ тегов шаблонов
	 * @param string $template
	 *        	：Содержимое исходного файла шаблона
	 * @return string
	 */
	function parse($template) {
		
		// Очистить разрывы строк в шаблонах
		// $template = @preg_replace('/[\n\r\t]/', '', $template);
		
		$template = @preg_replace ( "/\{tsUrl(.*?)\}/s", "{php echo tsurl\\1}", $template );
		$template = @preg_replace ( "/\{tsTitle(.*?)\}/s", "{php echo tsTitle\\1}", $template );
		
		$template = @preg_replace ( "/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template ); // html <!---->
		$template = @preg_replace ( "/\{($this->var_regexp)\}/", "<?php echo \\1;?>", $template ); // {}
		$template = @preg_replace ( "/\{($this->const_regexp)\}/", "<?php echo \\1;?>", $template ); // {}
		$template = @preg_replace ( "/(?<!\<\?php echo |\\\\)$this->var_regexp/", "<?php echo \\0;?>", $template ); // <?php echo
		$template = @preg_replace_callback ( "/\{php (.*?)\}/is",function( $m ){
			return $this->stripvTag('<?php '.$m[1].'?>');
		}, $template ); // php
		$template = @preg_replace_callback ( "/\{for (.*?)\}/is", function( $m ){
			return $this->stripvTag('<?php for('.$m[1].') {?>');
		}, $template ); // for
		$template = @preg_replace_callback ( "/\{elseif\s+(.+?)\}/is", function( $m ){
			return $this->stripvTag('<?php } elseif ('.$m[1].') { ?>');
		}, $template ); // elseif
		for($i = 0; $i < 3; $i ++) {
			$template = @preg_replace_callback ( "/\{loop\s+$this->vtag_regexp\s+$this->vtag_regexp\s+$this->vtag_regexp\}(.+?)\{\/loop\}/is", function( $m ){
				return $this->loopSection($m[1], $m[2], $m[3], $m[4]);
			}, $template );
			$template = @preg_replace_callback ( "/\{loop\s+$this->vtag_regexp\s+$this->vtag_regexp\}(.+?)\{\/loop\}/is", function( $m ){
				return $this->loopSection($m[1], '', $m[2], $m[3]);
			}, $template );
		}
		$template = @preg_replace_callback ( "/\{if\s+(.+?)\}/is", function( $m ){
			return $this->stripvTag('<?php if('.$m[1].') { ?>');
		}, $template ); // if
		$template = @preg_replace ( "/\{include\s+(.*?)\}/is", "<?php include \\1; ?>", $template ); // include
		
		$template = @preg_replace ( "/\{template\s+(\w+?)\}/is", "<?php include template('\\1'); ?>", $template ); 
		$template = @preg_replace_callback ( "/\{block (.*?)\}/is",function( $m ){
			return $this->stripBlock($m[1]);
		}, $template ); 
		$template = @preg_replace ( "/\{else\}/is", "<?php } else { ?>", $template );
		$template = @preg_replace ( "/\{\/if\}/is", "<?php } ?>", $template ); // 
		$template = @preg_replace ( "/\{\/for\}/is", "<?php } ?>", $template ); // 
		$template = @preg_replace ( "/$this->const_regexp/", "<?php echo \\1;?>", $template ); // note {else} ??
		$template = @preg_replace ( "/(\\\$[a-zA-Z_]\w+\[)([a-zA-Z_]\w+)\]/i", "\\1'\\2']", $template ); 
		/* $template = "<?php if(!defined('IN_TEST')) exit('Access Denied');?>\r\n$template"; */
		$template = "$template";
		
		return $template;
	}
	
	/**
	 * Замена соответствия регулярному выражению
	 *
	 * @param string $s
	 *        	：
	 * @return string
	 */
	function stripvTag($s) {
		return @preg_replace ( "/$this->vtag_regexp/is", "\\1", str_replace ( "\\\"", '"', $s ) );
	}
	function stripTagQuotes($expr) {
		$expr = @preg_replace ( "/\<\?php echo (\\\$.+?);\?\>/s", "{\\1}", $expr );
		$expr = str_replace ( "\\\"", "\"", @preg_replace ( "/\[\'([a-zA-Z0-9_\-\.\x7f-\xff]+)\'\]/s", "[\\1]", $expr ) );
		return $expr;
	}
	function stripv($vv) {
		$vv = str_replace ( '<?php', '', $vv );
		$vv = str_replace ( 'echo', '', $vv );
		$vv = str_replace ( ';', '', $vv );
		$vv = str_replace ( '?>', '', $vv );
		return $vv;
	}
	
	/**
	 * Замена BLOCK
	 *
	 * @param string $blockname
	 *        	：
	 * @param string $parameter
	 *        	：
	 * @return string
	 */
	function stripBlock($parameter) {
		return $this->stripTagQuotes ( "<?php Mooblock(\"$parameter\"); ?>" );
	}
	
	/**
	 * Замена LOOP
	 *
	 * @param string $arr
	 * @param string $k
	 * @param string $v
	 * @param string $statement
	 * @return string
	 */
	function loopSection($arr, $k, $v, $statement) {
		$arr = $this->stripvTag ( $arr );
		$k = $this->stripvTag ( $k );
		$v = $this->stripvTag ( $v );
		$statement = str_replace ( "\\\"", '"', $statement );
		return $k ? "<?php foreach((array)$arr as $k=>$v) {?>$statement<?php }?>" : "<?php foreach((array)$arr as $v) {?>$statement<?php } ?>";
	}
}
