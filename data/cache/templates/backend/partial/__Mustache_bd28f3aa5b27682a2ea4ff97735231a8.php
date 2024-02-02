<?php

class __Mustache_bd28f3aa5b27682a2ea4ff97735231a8 extends Mustache_Template
{
    private $lambdaHelper;

    public function renderInternal(Mustache_Context $context, $indent = '')
    {
        $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
        $buffer = '';

        $buffer .= $indent . '<div class="elz elzF wrap">
';
        $value = $context->find('isbutton');
        $buffer .= $this->section13ac039666e35bb94895f93d0ac2deab($context, $indent, $value);
        $value = $context->find('isbutton');
        if (empty($value)) {
            
            $buffer .= $indent . '    <label class="elz elzF item check ';
            $value = $this->resolveValue($context->find('align'), $context);
            $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
            $buffer .= '" for="';
            $value = $this->resolveValue($context->find('id'), $context);
            $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
            $buffer .= '">
';
            $buffer .= $indent . '        <input id="';
            $value = $this->resolveValue($context->find('id'), $context);
            $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
            $buffer .= '" ';
            $value = $this->resolveValue($context->find('input_attr'), $context);
            $buffer .= ($value === null ? '' : $value);
            $buffer .= ' class="elz elzFT ';
            $value = $this->resolveValue($context->find('input_class'), $context);
            $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
            $buffer .= '"/>
';
            $buffer .= $indent . '        <span class="elz checker ';
            $value = $this->resolveValue($context->find('item_class'), $context);
            $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
            $buffer .= '" ';
            $value = $this->resolveValue($context->find('item_attr'), $context);
            $buffer .= ($value === null ? '' : $value);
            $buffer .= '>
';
            $value = $context->find('isradio');
            $buffer .= $this->section0787d3b87bd3d8502b7a1d87769c12c2($context, $indent, $value);
            $value = $context->find('isradio');
            if (empty($value)) {
                
                $value = $context->find('isbox');
                $buffer .= $this->section455f702a0999569fed91231b95bf4273($context, $indent, $value);
                $value = $context->find('isbox');
                if (empty($value)) {
                    
                    $buffer .= $indent . '                    <span class="elz inner">
';
                    $buffer .= $indent . '                        ';
                    $value = $context->find('icon');
                    $buffer .= $this->sectionF979d403769a5ca48e04529d4f503749($context, $indent, $value);
                    $buffer .= '
';
                    $buffer .= $indent . '                        <i class="elz selector"></i>
';
                    $buffer .= $indent . '                        ';
                    $value = $context->find('icon');
                    $buffer .= $this->section5c95a8b24beb3cd219ac476022e9e3f2($context, $indent, $value);
                    $buffer .= '
';
                    $buffer .= $indent . '                    </span>
';
                }
            }
            $buffer .= $indent . '        </span>
';
            $buffer .= $indent . '        ';
            $value = $context->find('text');
            $buffer .= $this->section4622efafa37182c702606113be9af570($context, $indent, $value);
            $buffer .= '
';
            $buffer .= $indent . '    </label>
';
        }
        $buffer .= $indent . '    ';
        $value = $context->find('title');
        $buffer .= $this->section539c25eebec35ecc406c7edd8f4be154($context, $indent, $value);
        $buffer .= '
';
        $buffer .= $indent . '</div>';

        return $buffer;
    }

    private function section13ac039666e35bb94895f93d0ac2deab(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '
        <input id="{{id}}" {{{input_attr}}} class="elz elzFT {{input_class}}"/>
        <label class="elz elzF {{item_class}}" {{{item_attr}}} for="{{id}}">{{text}}</label>
';
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
                
                $buffer .= $indent . '        <input id="';
                $value = $this->resolveValue($context->find('id'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '" ';
                $value = $this->resolveValue($context->find('input_attr'), $context);
                $buffer .= ($value === null ? '' : $value);
                $buffer .= ' class="elz elzFT ';
                $value = $this->resolveValue($context->find('input_class'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '"/>
';
                $buffer .= $indent . '        <label class="elz elzF ';
                $value = $this->resolveValue($context->find('item_class'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '" ';
                $value = $this->resolveValue($context->find('item_attr'), $context);
                $buffer .= ($value === null ? '' : $value);
                $buffer .= ' for="';
                $value = $this->resolveValue($context->find('id'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '">';
                $value = $this->resolveValue($context->find('text'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '</label>
';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function sectionF979d403769a5ca48e04529d4f503749(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<i class="elz elzIc {{icon.first}}"></i>';
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
                
                $buffer .= '<i class="elz elzIc ';
                $value = $this->resolveValue($context->findDot('icon.first'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '"></i>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section5c95a8b24beb3cd219ac476022e9e3f2(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<i class="elz elzIc {{icon.second}}"></i>';
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
                
                $buffer .= '<i class="elz elzIc ';
                $value = $this->resolveValue($context->findDot('icon.second'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '"></i>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section0787d3b87bd3d8502b7a1d87769c12c2(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '
                <span class="elz inner">
                    {{#icon}}<i class="elz elzIc {{icon.first}}"></i>{{/icon}}
                    <i class="elz selector"></i>
                    {{#icon}}<i class="elz elzIc {{icon.second}}"></i>{{/icon}}
                </span>
            ';
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
                
                $buffer .= $indent . '                <span class="elz inner">
';
                $buffer .= $indent . '                    ';
                $value = $context->find('icon');
                $buffer .= $this->sectionF979d403769a5ca48e04529d4f503749($context, $indent, $value);
                $buffer .= '
';
                $buffer .= $indent . '                    <i class="elz selector"></i>
';
                $buffer .= $indent . '                    ';
                $value = $context->find('icon');
                $buffer .= $this->section5c95a8b24beb3cd219ac476022e9e3f2($context, $indent, $value);
                $buffer .= '
';
                $buffer .= $indent . '                </span>
';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section455f702a0999569fed91231b95bf4273(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '
                    <i class="elz elzIc ic-check2"></i>
                ';
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
                
                $buffer .= $indent . '                    <i class="elz elzIc ic-check2"></i>
';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section4622efafa37182c702606113be9af570(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<span class="elz text nowrap{{tx_class}}">{{text}}</span>';
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
                
                $buffer .= '<span class="elz text nowrap';
                $value = $this->resolveValue($context->find('tx_class'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '">';
                $value = $this->resolveValue($context->find('text'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '</span>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section539c25eebec35ecc406c7edd8f4be154(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<span class="elz elzFA elzF-text title">{{title}}</span>';
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
                
                $buffer .= '<span class="elz elzFA elzF-text title">';
                $value = $this->resolveValue($context->find('title'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '</span>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

}
