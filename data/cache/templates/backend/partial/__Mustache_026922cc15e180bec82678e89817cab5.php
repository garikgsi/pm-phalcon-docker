<?php

class __Mustache_026922cc15e180bec82678e89817cab5 extends Mustache_Template
{
    private $lambdaHelper;

    public function renderInternal(Mustache_Context $context, $indent = '')
    {
        $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
        $buffer = '';

        $buffer .= $indent . '<i class="elz main elzIc ';
        $value = $this->resolveValue($context->find('mn_ico_class'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= '" ';
        $value = $this->resolveValue($context->find('mn_ico_data'), $context);
        $buffer .= ($value === null ? '' : $value);
        $buffer .= '>';
        $value = $context->find('mn_sico');
        $buffer .= $this->section4613d3bf59d85650594d8ed9e356e7e4($context, $indent, $value);
        $buffer .= '</i>';

        return $buffer;
    }

    private function section4613d3bf59d85650594d8ed9e356e7e4(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<i class="elz gspot elzIc {{class}}" {{{data}}}>{{html}}</i>';
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
                
                $buffer .= '<i class="elz gspot elzIc ';
                $value = $this->resolveValue($context->find('class'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '" ';
                $value = $this->resolveValue($context->find('data'), $context);
                $buffer .= ($value === null ? '' : $value);
                $buffer .= '>';
                $value = $this->resolveValue($context->find('html'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '</i>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

}
