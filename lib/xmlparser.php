<?php

class XmlNode {
	public $tagName = NULL;
	public $text = "";
	public $parentNode = NULL;
	public $children = array();
	public $attributes = array();
	function __construct($myName, $myParent)
	{
		$this->tagName = $myName;
		$this->parentNode = $myParent;
	}
	function queryThis($selector)
	{
		if(!$selector)
		{
			return NULL;
		}
		if('.' == $selector[0])
		{
			if(array_key_exists('class', $this->attributes))
			{
				if(in_array(substr($selector, 1), explode(' ', $this->attributes['class'])))
				{
					return $this;
				}
			}
			return NULL;
		} elseif('#' == $selector[0])
		{
			if(array_key_exists('id', $this->attributes))
			{
				if(0 === strcmp(substr($selector, 1), $this->attributes['id']))
				{
					return $this;
				}
			}
			return NULL;
		} elseif('*' == $selector[0])
		{
			if(0 < strlen($this->tagName))
			{
				return $this;
			}
		}
		if(0 === strcmp($selector, $this->tagName))
		{
			return $this;
		}
		return NULL;
	}
	function querySelectorAll($selectors)
	{
		$splittedSelectors = explode(' ', $selectors);
		if($this->queryThis($splittedSelectors[0]))
		{
			$oldMatchingBranches = array();
			$oldMatchingBranches[] = $this;
			for($i = 1; $i < count($splittedSelectors); $i++)
			{
				$newMatchingBranches = array();
				for($j = 0; $j < count($oldMatchingBranches); $j++)
				{
					for($k = 0; $k < count($oldMatchingBranches[$j]->children); $k++)
					{
						if($oldMatchingBranches[$j]->children[$k]->queryThis($splittedSelectors[$i]))
						{
							$newMatchingBranches[] = $oldMatchingBranches[$j]->children[$k];
						}
					}
				}
				$oldMatchingBranches = $newMatchingBranches;
			}
			return $oldMatchingBranches;
		} else
		{
			return array();
		}
	}
}
function parseXml($xml_code)
{
	$TREAT_COMMENT_AS_TEXTCONTENT = TRUE;
	$within_tag = FALSE;
	$scanning_tag_name = FALSE;
	$within_attribute_value = FALSE;
	$scanning_attribute_name = FALSE;
	$within_escape = FALSE;
	$cur_element = NULL;
	$cur_tag_name = "";
	$cur_attribute_name = "";
	$cur_attribute_value = "";
	$xml_len = strlen($xml_code);
	$root_element = NULL;
	$do_parse_attributes = TRUE;
	$special_out_of_syntax = FALSE;
        $unbalanced_tag_with_slash = FALSE;
	for($i = 0; $i < $xml_len; $i++)
	{
		$c = $xml_code[$i];
		// if we are not in a comment
		if(!$special_out_of_syntax)
		{
			if(!$within_tag)
			{
				if('<' == $c)
				{
					$within_tag = TRUE;
					$scanning_tag_name = TRUE;
					$cur_tag_name = "";
				} else
				{
					if($cur_element)
					{
						$cur_element->text .= $c;
					}
				}
			// TRUE == $within_tag
			} else
			{
				if($scanning_tag_name)
				{
					if(is_space($c) || '>' == $c)
					{
						// if tagName starts with a question or exclamation mark
						if(0 < strlen($cur_tag_name) && ('?' == $cur_tag_name[0] || 0 === strpos($cur_tag_name, '!')))
						{
	
							//    Standard Comments <!-- -->
							if(0 === strpos($cur_tag_name, '!--'))
							{
								$scanning_tag_name = FALSE;
								$do_parse_attributes = FALSE;
								$within_tag = FALSE;
								$special_out_of_syntax = TRUE;
								$special_ending_chars_str = '-->';
								$special_ending_chars_index = 0;
								if($TREAT_COMMENT_AS_TEXTCONTENT
								 && $cur_element)
								{
									$cur_element->text .= '<' . $cur_tag_name . $c;
								}
							//    CDATA stuff like <![CDATA[.....]]>
							} elseif(0 === strpos($cur_tag_name, '![CDATA['))
							{
								$scanning_tag_name = FALSE;
								$do_parse_attributes = FALSE;
								$within_tag = FALSE;
								$special_out_of_syntax = TRUE;
								$special_ending_chars_str = ']]>';
								$special_ending_chars_index = 0;
								if($TREAT_COMMENT_AS_TEXTCONTENT
								 && $cur_element)
								{
									$cur_element->text .= '<' . $cur_tag_name . $c;
								}
							} else {
								// do nothing,
								// i.e. for "<?" xml tags...
								$do_parse_attributes = FALSE;
							}
						// ending tag like </tagName>
						} elseif(0 < strlen($cur_tag_name) && '/' == $cur_tag_name[0])
						{
							// maybe TODO: check if $cur_tag_name (without slash) fits current node's tagName
							if($cur_element->parentNode)
							{
								$cur_element = $cur_element->parentNode;
							} else
							{
								return $root_element;
							}
						// beginning tag like <tagName>
						} else
						{
							$new_node = new XmlNode($cur_tag_name, $cur_element);
							if(NULL !== $cur_element)
							{
								$cur_element->children[] = $new_node;
							} else if(!$new_node->parentNode)
							{
								$root_element = $new_node;
							}
							$cur_element = $new_node;
							$do_parse_attributes = TRUE;
						}
						if('>' == $c)
						{
							$within_tag = FALSE;
						} else
						{
							$scanning_attribute_name = TRUE;
						}
						$scanning_tag_name = FALSE;
					} else
					{
						$cur_tag_name .= $c;
					}
				// FALSE == $scanning_tag_name
				} else
				{
					if(FALSE === $within_attribute_value)
					{
						if(is_space($c) || '>' == $c)
						{
							if(0 < strlen($cur_attribute_name) && $cur_element && $do_parse_attributes)
							{
								$cur_element->attributes[$cur_attribute_name] = $cur_attribute_value;
							}
							if('>' == $c)
							{
								$within_tag = FALSE;
								if($unbalanced_tag_with_slash)
								{
									$unbalanced_tag_with_slash = FALSE;
									if($cur_element->parentNode)
									{
										$cur_element = $cur_element->parentNode;
									} else
									{
										$cur_element = $root_element;
									}
								}
							} else
							{
								$scanning_attribute_name = TRUE;
							}
							$cur_attribute_name = "";
							$cur_attribute_value = "";
						// no space and $c != '>'
						} else
						{
							if($scanning_attribute_name)
							{
								if('=' == $c)
								{
									$scanning_attribute_name = FALSE;
								} else
								{
									$cur_attribute_name .= $c;
								}
							} else
							{
								if('"' == $c)
								{
									$within_attribute_value = TRUE;
								} elseif('/' == $c)
								{
									$unbalanced_tag_with_slash = TRUE;
								} else
								{
									$cur_attribute_value .= $c;
								}
							}
						}
					// TRUE == $within_attribute
					} else
					{
						if($within_escape)
						{
							switch($c)
							{
								case "n":
									$cur_attribute_value .= "\n";
									break;
								case "r":
									$cur_attribute_value .= "\r";
									break;
								case "t":
									$cur_attribute_value .= "\t";
									break;
								default:
									$cur_attribute_value .= $c;
								
							}
							$within_escape = false;
						} else
						{
							if("\\" == $c)
							{
								$within_escape = TRUE;
							} elseif("\"" == $c)
							{
								$within_attribute_value = FALSE;
							} else
							{
								$cur_attribute_value .= $c;
							}
						}
					}
				}
	
			// TRUE == $special_out_of_syntax
			// if we are in a comment like <!--  ...  -->
			}
		} else
		{
			if($TREAT_COMMENT_AS_TEXTCONTENT
			 && $cur_element)
			{
				$cur_element->text .= $c;
			}
			if(ord($c) === ord($special_ending_chars_str[$special_ending_chars_index]))
			{
				$special_ending_chars_index += 1;
				if(strlen($special_ending_chars_str) <= $special_ending_chars_index)
				{
					$special_out_of_syntax = FALSE;
				}
			} else
			{
				$special_ending_chars_index = 0;
			}
		}
	}
	return $root_element;
}
function is_space($c)
{
	if(" " == $c || "\n" == $c || "\t" == $c || "\r" == $c)
	{
		return TRUE;
	} else
	{
		return FALSE;
	}
}
?>
