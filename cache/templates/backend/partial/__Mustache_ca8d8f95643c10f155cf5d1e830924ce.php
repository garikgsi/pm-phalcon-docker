<?php

class __Mustache_ca8d8f95643c10f155cf5d1e830924ce extends Mustache_Template
{
    private $lambdaHelper;

    public function renderInternal(Mustache_Context $context, $indent = '')
    {
        $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
        $buffer = '';

        $buffer .= $indent . '<div ';
        $value = $context->find('id_tray');
        $buffer .= $this->section2fee6ade14cab3d273c37b62a4e550e8($context, $indent, $value);
        $buffer .= ' class="elz tbIt tbWrap left mainTray">
';
        $buffer .= $indent . '    <div class="elz epBF tbIt tbWrapFlex">
';
        $buffer .= $indent . '        <div class="elz epBF tbIt tbWrapIn">
';
        $buffer .= $indent . '
';
        $buffer .= $indent . '            <div ';
        $value = $context->find('id_menu');
        $buffer .= $this->sectionE54e397071b16f36d8bd0c9478cf4e48($context, $indent, $value);
        $buffer .= ' class="elz tbIt tbTool default medium link txt elzCSSNtrayStart">
';
        $buffer .= $indent . '                <div class="elz toolAction">
';
        $buffer .= $indent . '                    <div class="elz item lines">
';
        $buffer .= $indent . '                        <i class="elz line"></i>
';
        $buffer .= $indent . '                        <i class="elz line"></i>
';
        $buffer .= $indent . '                        <i class="elz line"></i>
';
        $buffer .= $indent . '                    </div>
';
        $buffer .= $indent . '                </div>
';
        $buffer .= $indent . '                <div id="';
        $value = $this->resolveValue($context->find('id_tray'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= 'Avatar" class="elz elzIcon rad larger elzPLT" data-bg="light 200">
';
        $value = $context->find('avatar_ver');
        $buffer .= $this->sectionC2ec2ac5f1ec4ddf9dbd8357347eb3bc($context, $indent, $value);
        $value = $context->find('avatar_ver');
        if (empty($value)) {
            
            $buffer .= $indent . '                    <i class="elz main medium elzIc ic-eliza-logo elzPLT shD" data-fn="white"></i>
';
        }
        $buffer .= $indent . '
';
        $buffer .= $indent . '                </div>
';
        $buffer .= $indent . '                <div ';
        $value = $context->find('id_text');
        $buffer .= $this->section036fe42eecfd33d5d89c03ba28ce98b7($context, $indent, $value);
        $buffer .= ' class="elz tbText right">
';
        $buffer .= $indent . '                    <div ';
        $value = $context->find('id_text');
        $buffer .= $this->section27610cf752e0a025b74b19f25c93daba($context, $indent, $value);
        $buffer .= ' class="elz txtWrap bold">';
        $value = $this->resolveValue($context->find('user_name'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= '</div>
';
        $buffer .= $indent . '                    <div ';
        $value = $context->find('id_text');
        $buffer .= $this->sectionB5e4b8b61567d5f6b1a46066a00e3090($context, $indent, $value);
        $buffer .= ' class="elz txtWrap fn-8">';
        $value = $this->resolveValue($context->find('user_text'), $context);
        $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
        $buffer .= '</div>
';
        $buffer .= $indent . '                </div>
';
        $buffer .= $indent . '            </div>
';
        $buffer .= $indent . '            <div class="elz tbIt tbGroup trayTools">
';
        $buffer .= $indent . '                <div ';
        $value = $context->find('id_tools');
        $buffer .= $this->section6b72c66283c68d55ac9840958788282d($context, $indent, $value);
        $buffer .= ' class="elz epBF tbIt tbGroupIn"></div>
';
        $buffer .= $indent . '            </div>
';
        $buffer .= $indent . '        </div>
';
        $buffer .= $indent . '    </div>
';
        $buffer .= $indent . '</div>';

        return $buffer;
    }

    private function section2fee6ade14cab3d273c37b62a4e550e8(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = 'id="{{id_tray}}"';
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
                
                $buffer .= 'id="';
                $value = $this->resolveValue($context->find('id_tray'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '"';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function sectionE54e397071b16f36d8bd0c9478cf4e48(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = 'id="{{id_menu}}"';
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
                
                $buffer .= 'id="';
                $value = $this->resolveValue($context->find('id_menu'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '"';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function sectionC2ec2ac5f1ec4ddf9dbd8357347eb3bc(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '
                    <div class="elz main image fill">
                        <img class="elz img" src="/uploads/avatars/user_{{user_id}}_{{avatar_ver}}_32.png">
                    </div>
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
                
                $buffer .= $indent . '                    <div class="elz main image fill">
';
                $buffer .= $indent . '                        <img class="elz img" src="/uploads/avatars/user_';
                $value = $this->resolveValue($context->find('user_id'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '_';
                $value = $this->resolveValue($context->find('avatar_ver'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '_32.png">
';
                $buffer .= $indent . '                    </div>
';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section036fe42eecfd33d5d89c03ba28ce98b7(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = 'id="{{id_text}}"';
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
                
                $buffer .= 'id="';
                $value = $this->resolveValue($context->find('id_text'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '"';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section27610cf752e0a025b74b19f25c93daba(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = 'id="{{id_text}}Nick"';
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
                
                $buffer .= 'id="';
                $value = $this->resolveValue($context->find('id_text'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= 'Nick"';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function sectionB5e4b8b61567d5f6b1a46066a00e3090(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = 'id="{{id_text}}Status"';
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
                
                $buffer .= 'id="';
                $value = $this->resolveValue($context->find('id_text'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= 'Status"';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section6b72c66283c68d55ac9840958788282d(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = 'id="{{id_tools}}"';
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
                
                $buffer .= 'id="';
                $value = $this->resolveValue($context->find('id_tools'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '"';
                $context->pop();
            }
        }
    
        return $buffer;
    }

}
