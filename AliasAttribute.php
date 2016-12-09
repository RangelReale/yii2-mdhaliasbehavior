<?php

namespace RangelReale\mdhaliasbehavior;

use yii\base\Object;
use RangelReale\mdh\IDataHandler;
use RangelReale\mdh\DataConversionException;

/**
 * Class AliasAttribute
 * @property string $value
 */
class AliasAttribute extends Object
{
    /**
     * @var AliasBehavior
     */    
    public $behavior;
    
    /**
     * @var string
     */
    public $originalAttribute;    

    /**
     * @var string
     */
    public $targetAttribute;    
    
    /**
     * @var string
     */    
    public $dataType = 'raw';

    /**
     * @var string|null
     */    
    public $dataTypeFormat = IDataHandler::FORMAT_INPUT;
    
    /**
     * Datatype used for source. If empty, uses [[dataType]]
     * @var string|array
     */    
    public $sourceDataType;
    
    /**
     * Datatype used for output. If empty, uses [[dataType]]
     * @var string|array
     */    
    public $outputDataType;
    
    /**
     * @var string
     */    
    public $nullValue;
    
    /**
     * Value that was set (only if error)
     * @var string
     */    
    protected $_value;
    
    /**
     * @var string
     */    
    private $_error;
    
    public function init()
    {
        if (is_null($this->sourceDataType))
            $this->sourceDataType = $this->dataType;
        if (is_null($this->outputDataType))
            $this->outputDataType = $this->dataType;
    }
    
    function __toString()
    {
        return $this->getValue();
    }
    
    function __invoke()
    {
        return $this->getValue();
    }
    
    /**
     * @return string
     */    
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @return string
     */    
    public function getValue()
    {
        if (!is_null($this->_value))
            return $this->_value;
        
        try {
            $originalValue = $this->behavior->owner->{$this->originalAttribute};
            if ($originalValue === null)
                return $this->nullValue;
            
            $originalValue = $this->getMdh()->parse($this->behavior->sourceConverter, 
                $this->sourceDataType, $originalValue, ['format'=>$this->dataTypeFormat, '_aliasattribute'=>$this]);

            return $this->getMdh()->format($this->behavior->outputConverter, 
                $this->outputDataType, $originalValue, ['format'=>$this->dataTypeFormat, '_aliasattribute'=>$this]);
        } catch (DataConversionException $e) {
            return $this->nullValue;
        }        
    }
    
    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $originalValue = $value;
        
        try
        {
            $value = $this->getMdh()->parse($this->behavior->outputConverter, 
                $this->outputDataType, $value, ['format'=>$this->dataTypeFormat, '_aliasattribute'=>$this]);

            $value = $this->getMdh()->format($this->behavior->sourceConverter, 
                $this->sourceDataType, $value, ['format'=>$this->dataTypeFormat, '_aliasattribute'=>$this]);
            
            $this->behavior->owner->{$this->originalAttribute} = $value;        
            
            $this->_value = null;
            $this->_error = null;
        } catch (DataConversionException $e) {
            $this->_value = $originalValue;
            $this->_error = $e->getMessage();
        }
    }
    
    public function reset()
    {
        $this->_value = null;
        $this->_error = null;
    }
    
    private function getMdh()
    {
        if (isset($this->behavior->mdh))
            return $this->behavior->mdh;
        return \Yii::$app->mdh;
    }
}