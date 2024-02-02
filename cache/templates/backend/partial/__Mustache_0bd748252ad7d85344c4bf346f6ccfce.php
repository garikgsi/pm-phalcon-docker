<?php

class __Mustache_0bd748252ad7d85344c4bf346f6ccfce extends Mustache_Template
{
    private $lambdaHelper;

    public function renderInternal(Mustache_Context $context, $indent = '')
    {
        $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
        $buffer = '';

        $buffer .= $indent . '<';
        $value = $this->resolveValue($context->find('tag'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= ' class="elz elzF item ';
        $value = $this->resolveValue($context->find('input_class'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= '" ';
        $value = $this->resolveValue($context->find('input_attr'), $context);
        $buffer .= ($value === null ? '' : $value);
        $buffer .= ' ';
        $value = $this->resolveValue($context->find('input_data'), $context);
        $buffer .= ($value === null ? '' : $value);
        $buffer .= ' ';
        $value = $context->find('short');
        $buffer .= $this->sectionB4eb47d630fbc9d5da44c01a942b6d27($context, $indent, $value);
        $buffer .= '>';
        $value = $this->resolveValue($context->find('text'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $value = $context->find('short');
        if (empty($value)) {
            
            $buffer .= '</';
            $value = $this->resolveValue($context->find('tag'), $context);
            $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
            $buffer .= '>';
        }

        return $buffer;
    }

    private function sectionB4eb47d630fbc9d5da44c01a942b6d27(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '/';
            $result = (string) call_user_func($value, $source, $this->lambdaHelper);
            if (strpos($result, '{{') === false) {
                $buffer .= $result;
            } else {
                $buffer .= $this->mustache
                    ->loadLambda($result)
                    ->renderInternal($context);
            }
        } elseif (!empty($value)) {
            $values = $this->isIterable($value) ? $value : array($value);
            foreach ($values as $value) {
                $context->push($value);
                
                $buffer .= '/';
                $context->pop();
            }
        }
    
        return $buffer;
    }

}
