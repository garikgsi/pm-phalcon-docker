<?php

class __Mustache_ef706a8def7a77ca7048d58a07c3b7ad extends Mustache_Template
{
    private $lambdaHelper;

    public function renderInternal(Mustache_Context $context, $indent = '')
    {
        $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
        $buffer = '';

        $buffer .= $indent . '<div class="elizaIForm elz elzF wrap">
';
        $buffer .= $indent . '    <div class="elz elzF item parent';
        $value = $context->find('file');
        $buffer .= $this->section4a258822a9d6085457c9b1394e88c326($context, $indent, $value);
        $value = $context->find('select');
        $buffer .= $this->section0632bb4f61d13ec3c3c491d5eef3a6a0($context, $indent, $value);
        $buffer .= '">
';
        $buffer .= $indent . '        <div class="elz elzF wrap compiled flexible fixed">
';
        $buffer .= $indent . '            ';
        $value = $context->find('input_base');
        $buffer .= $this->section9a16fcf45476f8865dab8916fbcc0e6a($context, $indent, $value);
        $buffer .= '
';
        $buffer .= $indent . '            ';
        $value = $context->find('input_right');
        $buffer .= $this->section3baf1eff4d55e6c467b084947ac83538($context, $indent, $value);
        $buffer .= '
';
        $buffer .= $indent . '        </div>
';
        $buffer .= $indent . '        ';
        $value = $context->find('file');
        $buffer .= $this->section0a39f9a0d2796ccd3c2d12ab001a33f1($context, $indent, $value);
        $buffer .= '
';
        $buffer .= $indent . '        ';
        $value = $context->find('select');
        $buffer .= $this->sectionA1e2d1ae157c73f1a6d8d004124d8fe5($context, $indent, $value);
        $buffer .= '
';
        $buffer .= $indent . '    </div>
';
        $buffer .= $indent . '    ';
        $value = $context->find('hint');
        $buffer .= $this->section4adde4e8b485545438bc1cbd58e5bcfc($context, $indent, $value);
        $buffer .= '
';
        $buffer .= $indent . '</div>';

        return $buffer;
    }

    private function section4a258822a9d6085457c9b1394e88c326(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = ' upload';
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
                
                $buffer .= ' upload';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section0632bb4f61d13ec3c3c491d5eef3a6a0(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = ' select';
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
                
                $buffer .= ' select';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section9a16fcf45476f8865dab8916fbcc0e6a(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '{{> forms/base_input}}';
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
                
                if ($partial = $this->mustache->loadPartial('forms/base_input')) {
                    $buffer .= $partial->renderInternal($context);
                }
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section3baf1eff4d55e6c467b084947ac83538(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<div class="elz elzF wrap auto">{{> forms/common_f_input}}</div>';
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
                
                $buffer .= '<div class="elz elzF wrap auto">';
                if ($partial = $this->mustache->loadPartial('forms/common_f_input')) {
                    $buffer .= $partial->renderInternal($context);
                }
                $buffer .= '</div>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section62b4404864f648ca17c94131467c9e32(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = ' id="{{id}}"';
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
                
                $buffer .= ' id="';
                $value = $this->resolveValue($context->find('id'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '"';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section0a39f9a0d2796ccd3c2d12ab001a33f1(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<input{{#id}} id="{{id}}"{{/id}} name="{{name}}" type="file" class="elz elzF-fake elizaIFormFile" data-type="{{type}}">';
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
                
                $buffer .= '<input';
                $value = $context->find('id');
                $buffer .= $this->section62b4404864f648ca17c94131467c9e32($context, $indent, $value);
                $buffer .= ' name="';
                $value = $this->resolveValue($context->find('name'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '" type="file" class="elz elzF-fake elizaIFormFile" data-type="';
                $value = $this->resolveValue($context->find('type'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '">';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function sectionA1e2d1ae157c73f1a6d8d004124d8fe5(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '<select {{{sl_attr}}} class="elzF elzF-fake {{sl_class}}">{{{html}}}</select>';
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
                
                $buffer .= '<select ';
                $value = $this->resolveValue($context->find('sl_attr'), $context);
                $buffer .= ($value === null ? '' : $value);
                $buffer .= ' class="elzF elzF-fake ';
                $value = $this->resolveValue($context->find('sl_class'), $context);
                $buffer .= ($value === null ? '' : htmlspecialchars($value, 2, 'UTF-8'));
                $buffer .= '">';
                $value = $this->resolveValue($context->find('html'), $context);
                $buffer .= ($value === null ? '' : $value);
                $buffer .= '</select>';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section4adde4e8b485545438bc1cbd58e5bcfc(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '{{> forms/hint}}';
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
                
                if ($partial = $this->mustache->loadPartial('forms/hint')) {
                    $buffer .= $partial->renderInternal($context);
                }
                $context->pop();
            }
        }
    
        return $buffer;
    }

}
