<?php

namespace Search\SphinxsearchBundle\Extension;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * Search\SphinxsearchBundle\Extension\Field
 */
class Field extends FunctionNode
{

    private $field = null;
    private $values = array();

    /**
     * {@inheritdoc}
     *
     * @param \Doctrine\ORM\Query\Parser $parser
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
	$parser->match(Lexer::T_IDENTIFIER);
	$parser->match(Lexer::T_OPEN_PARENTHESIS);
	// Do the field.
	$this->field = $parser->ArithmeticPrimary();
	// Add the strings to the values array. FIELD must
	// be used with at least 1 string not including the field.
	$lexer = $parser->getLexer();
	while (1 > count($this->values) ||
	$lexer->lookahead['type'] != Lexer::T_CLOSE_PARENTHESIS) {
	    $parser->match(Lexer::T_COMMA);
	    $this->values[] = $parser->ArithmeticPrimary();
	}

	$parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker
     * @return string
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
	$query = 'FIELD(';
	$query .= $this->field->dispatch($sqlWalker);
	$query .= ',';
	for ($i = 0; $i < count($this->values); $i++) {
	    if (0 < $i) {
		$query .= ',';
	    }
	    $query .= $this->values[$i]->dispatch($sqlWalker);
	}
	$query .= ')';

	return $query;
    }

}