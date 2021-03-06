<?php

abstract class ExceptionTags {
	
	static $index = array('http://local/Dude' => 'HTML');
	
}

class HTML extends ExceptionTags {
	
	static $tagsCanbeClosedInline = array('br', 'img', 'option', 'input', 'meta', 'link', 'frame', 'base', 'button', 'br');
	
	static $tags = 
		  array(
			'html', 
			'xhtml', 
			'head', 
			'meta', 
			'link', 
			'title', 
			'body', 
			'script', 
			'style', 
			'div', 
			'span', 
			'ul', 
			'li', 
			'b', 
			'pre', 
			'tt', 
			'i', 
			'a', 
			'img', 
			'form', 
			'input', 
			'select', 
			'option', 
			'textarea', 
			'h1', 
			'h2', 
			'h3', 
			'h4', 
			'h5', 
			'h6', 
			'hr', 
			'p', 
			'br', 
			'table', 
			'tbody', 
			'thead', 
			'tfoot', 
			'tr', 
			'th', 
			'td', 
			'caption', 
			'article', 
			'frameset', 
			'frame', 
			'address', 
			'blockquote', 
			'button', 
			'base', 
			'applet', 
			'area', 
			'abbr', 
			'acronym', 
			'isindex', 
			'big', 
			'bdo', 
			'basefont', 
			'center', 
			'code', 
			'col', 
			'colgroup', 
			'dd', 
			'del', 
			'dfn', 
			'dir', 
			'dl', 
			'dt', 
			'em', 
			'fieldset', 
			'font', 
			'iframe', 
			'ins', 
			'kbd', 
			'label', 
			'legend', 
			'map', 
			'menu', 
			'noframes', 
			'noscript', 
			'object', 
			'ol', 
			'optgroup', 
			'param', 
			'q', 
			's', 
			'samp', 
			'small', 
			'strike', 
			'strong', 
			'u', 
			'ul', 
			'ol',
			'var', 
			'canvas', 
			'command', 
			'article', 
			'aside', 
			'audio', 
			'datalist', 
			'embed', 
			'eventsource', 
			'figcaption', 
			'figure', 
			'footer', 
			'hgroup', 
			'keygen', 
			'header', 
			'mark', 
			'meter', 
			'nav', 
			'output', 
			'ruby', 
			'rp', 
			'rt', 
			'progress', 
			'section', 
			'source', 
			'summary', 
			'time', 
			'video', 
			'wbr',
			'sub',
			'sup'
		  );
	
}


?>
