<?php

class __Mustache_1a1d73198bb183a314d0454054166949 extends Mustache_Template
{
    private $lambdaHelper;

    public function renderInternal(Mustache_Context $context, $indent = '')
    {
        $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
        $buffer = '';

        $buffer .= $indent . '<';
        $value = $this->resolveValue($context->find('icn_tag'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= ' class="elz elzIcon ';
        $value = $this->resolveValue($context->find('icn_class'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= '" ';
        $value = $this->resolveValue($context->find('icn_data'), $context);
        $buffer .= ($value === null ? '' : $value);
        $buffer .= '>';
        $value = $context->find('icn_image');
        $buffer .= $this->section1358cc3c1a788ffffd42250bbb0a85e1($context, $indent, $value);
        $value = $context->find('icn_image');
        if (empty($value)) {
            
            $value = $context->find('icn_icons');
            $buffer .= $this->sectionF4ad68fdb2568acb080e32b51e9b31e6($context, $indent, $value);
        }
        $value = $context->find('noty');
        $buffer .= $this->sectionE66298563d0c493d55c0dcb7bcd571d2($context, $indent, $value);
        $buffer .= '</';
        $value = $this->resolveValue($context->find('icn_tag'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= '>';

        return $buffer;
    }

    private function section1358cc3c1a788ffffd42250bbb0a85e1(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<div class="elz main {{im_class}}"><img class="elz img" alt="{{im_alt}}" src="{{im_src}}" /></div>';
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
                
                $buffer .= '<div class="elz main ';
                $value = $this->resolveValue($context->find('im_class'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '"><img class="elz img" alt="';
                $value = $this->resolveValue($context->find('im_alt'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '" src="';
                $value = $this->resolveValue($context->find('im_src'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '" /></div>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function sectionF4ad68fdb2568acb080e32b51e9b31e6(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '{{> common/icon_main}}';
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
                
                if ($partial = $this->mustache->loadPartial('common/icon_main')) {
                    $buffer .= $partial->renderInternal($context);
                }
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function sectionE66298563d0c493d55c0dcb7bcd571d2(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<div class="elz elzCLScount {{noty_class}}" {{{noty_data}}}><i class="elz elzIc {{noty_icon}}">{{noty_html}}</i></div>';
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
                
                $buffer .= '<div class="elz elzCLScount ';
                $value = $this->resolveValue($context->find('noty_class'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '" ';
                $value = $this->resolveValue($context->find('noty_data'), $context);
                $buffer .= ($value === null ? '' : $value);
                $buffer .= '><i class="elz elzIc ';
                $value = $this->resolveValue($context->find('noty_icon'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '">';
                $value = $this->resolveValue($context->find('noty_html'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '</i></div>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

}
