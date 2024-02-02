<?php

class __Mustache_3b8cf352304651377d8fa7b9973585a8 extends Mustache_Template
{
    private $lambdaHelper;

    public function renderInternal(Mustache_Context $context, $indent = '')
    {
        $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
        $buffer = '';

        $buffer .= $indent . '<';
        $value = $this->resolveValue($context->find('tag'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= ' class="elz elzFA elzF-icon ';
        $value = $this->resolveValue($context->find('icon_class'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= '" ';
        $value = $this->resolveValue($context->find('icon_attr'), $context);
        $buffer .= ($value === null ? '' : $value);
        $buffer .= ' ';
        $value = $this->resolveValue($context->find('icon_data'), $context);
        $buffer .= ($value === null ? '' : $value);
        $buffer .= '>';
        $value = $context->find('icon');
        $buffer .= $this->section61878fe87c9b6a752148a553c5f9725f($context, $indent, $value);
        $value = $context->find('texted');
        $buffer .= $this->section36ce0ac3e39f480dfbcc62a1f7dd3616($context, $indent, $value);
        $buffer .= '</';
        $value = $this->resolveValue($context->find('tag'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= '>';

        return $buffer;
    }

    private function section61878fe87c9b6a752148a553c5f9725f(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '{{> common/icon}}';
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
                
                if ($partial = $this->mustache->loadPartial('common/icon')) {
                    $buffer .= $partial->renderInternal($context);
                }
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section36ce0ac3e39f480dfbcc62a1f7dd3616(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<span class="text">{{text}}</span>';
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
                
                $buffer .= '<span class="text">';
                $value = $this->resolveValue($context->find('text'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '</span>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

}
