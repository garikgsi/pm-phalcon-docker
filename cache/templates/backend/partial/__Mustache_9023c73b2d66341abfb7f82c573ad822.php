<?php

class __Mustache_9023c73b2d66341abfb7f82c573ad822 extends Mustache_Template
{
    private $lambdaHelper;

    public function renderInternal(Mustache_Context $context, $indent = '')
    {
        $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
        $buffer = '';

        $buffer .= $indent . '<';
        $value = $this->resolveValue($context->find('tool_tag'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= ' class="elz tbIt tbTool ';
        $value = $this->resolveValue($context->find('tool_class'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= '" ';
        $value = $this->resolveValue($context->find('tool_data'), $context);
        $buffer .= ($value === null ? '' : $value);
        $buffer .= '>';
        $value = $context->find('icon');
        $buffer .= $this->section61878fe87c9b6a752148a553c5f9725f($context, $indent, $value);
        $value = $context->find('tool_text');
        $buffer .= $this->section7070384131942e66f846cb9c825082b5($context, $indent, $value);
        $value = $context->find('tip');
        $buffer .= $this->sectionC525ba6effced2dd4ca26022452c9dc5($context, $indent, $value);
        $buffer .= '</';
        $value = $this->resolveValue($context->find('tool_tag'), $context);
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

    private function section7070384131942e66f846cb9c825082b5(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<div class="elz tbText {{tool_tclass}}">{{tool_text}}</div>';
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
                
                $buffer .= '<div class="elz tbText ';
                $value = $this->resolveValue($context->find('tool_tclass'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '">';
                $value = $this->resolveValue($context->find('tool_text'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '</div>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function sectionC525ba6effced2dd4ca26022452c9dc5(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<div class="elz elzTTwrap {{tip_class}}"><div class="elz elzTooltip nowrap">{{tip_text}}</div></div>';
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
                
                $buffer .= '<div class="elz elzTTwrap ';
                $value = $this->resolveValue($context->find('tip_class'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '"><div class="elz elzTooltip nowrap">';
                $value = $this->resolveValue($context->find('tip_text'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '</div></div>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

}
