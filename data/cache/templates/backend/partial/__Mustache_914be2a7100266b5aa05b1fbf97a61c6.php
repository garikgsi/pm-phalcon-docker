<?php

class __Mustache_914be2a7100266b5aa05b1fbf97a61c6 extends Mustache_Template
{
    private $lambdaHelper;

    public function renderInternal(Mustache_Context $context, $indent = '')
    {
        $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
        $buffer = '';

        $buffer .= $indent . '<div class="elz elzCLSenginePanel light float mainTaskbar" ';
        $value = $this->resolveValue($context->find('panel_data'), $context);
        $buffer .= ($value === null ? '' : $value);
        $buffer .= '>
';
        $buffer .= $indent . '    <div class="elz epBF elzCLStoolbar light elzPLT" data-bg="grey 900">
';
        $buffer .= $indent . '
';
        $buffer .= $indent . '        ';
        $value = $this->resolveValue($context->find('html_tray'), $context);
        $buffer .= ($value === null ? '' : $value);
        $buffer .= '
';
        $buffer .= $indent . '
';
        $value = $context->find('html_wincotrol');
        $buffer .= $this->section51ea9a6185f91d747d6e805727ec899a($context, $indent, $value);
        $value = $context->find('html_center');
        $buffer .= $this->sectionDc16427885e328262960464a20ff167c($context, $indent, $value);
        $buffer .= $indent . '    </div>
';
        $buffer .= $indent . '</div>';

        return $buffer;
    }

    private function section51ea9a6185f91d747d6e805727ec899a(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '
        <div class="elz tbIt tbWrap right winControls">
            <div class="elz epBF tbIt tbWrapFlex">
                <div class="elz epBF tbIt tbWrapIn">
                    {{{html_wincotrol}}}
                </div>
            </div>
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
                
                $buffer .= $indent . '        <div class="elz tbIt tbWrap right winControls">
';
                $buffer .= $indent . '            <div class="elz epBF tbIt tbWrapFlex">
';
                $buffer .= $indent . '                <div class="elz epBF tbIt tbWrapIn">
';
                $buffer .= $indent . '                    ';
                $value = $this->resolveValue($context->find('html_wincotrol'), $context);
                $buffer .= ($value === null ? '' : $value);
                $buffer .= '
';
                $buffer .= $indent . '                </div>
';
                $buffer .= $indent . '            </div>
';
                $buffer .= $indent . '        </div>
';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function sectionDc16427885e328262960464a20ff167c(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '
        <div class="elz tbIt tbWrap contentCenter {{center_class}}" {{{center_data}}}>
            <div class="elz epBF tbIt tbWrapFlex">
                <div class="elz epBF tbIt tbWrapIn epGrow">
                    {{{html_center}}}
                </div>
            </div>
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
                
                $buffer .= $indent . '        <div class="elz tbIt tbWrap contentCenter ';
                $value = $this->resolveValue($context->find('center_class'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '" ';
                $value = $this->resolveValue($context->find('center_data'), $context);
                $buffer .= ($value === null ? '' : $value);
                $buffer .= '>
';
                $buffer .= $indent . '            <div class="elz epBF tbIt tbWrapFlex">
';
                $buffer .= $indent . '                <div class="elz epBF tbIt tbWrapIn epGrow">
';
                $buffer .= $indent . '                    ';
                $value = $this->resolveValue($context->find('html_center'), $context);
                $buffer .= ($value === null ? '' : $value);
                $buffer .= '
';
                $buffer .= $indent . '                </div>
';
                $buffer .= $indent . '            </div>
';
                $buffer .= $indent . '        </div>
';
                $context->pop();
            }
        }
    
        return $buffer;
    }

}
