<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yii\widgets;

use Yii;
use yii\base\Component;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\base\Model;
use yii\web\JsExpression;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ActiveField extends Component
{
	/**
	 * @var ActiveForm the form that this field is associated with.
	 */
	public $form;
	/**
	 * @var Model the data model that this field is associated with
	 */
	public $model;
	/**
	 * @var string the model attribute that this field is associated with
	 */
	public $attribute;
	/**
	 * @var array the HTML attributes (name-value pairs) for the field container tag.
	 * The values will be HTML-encoded using [[Html::encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 * The following special options are recognized:
	 *
	 * - tag: the tag name of the container element. Defaults to "div".
	 */
	public $options = array(
		'class' => 'form-group',
	);
	/**
	 * @var string the template that is used to arrange the label, the input field, the error message and the hint text.
	 * The following tokens will be replaced when [[render()]] is called: `{label}`, `{input}`, `{error}` and `{hint}`.
	 */
	public $template = "{label}\n{input}\n{error}\n{hint}";
	/**
	 * @var array the default options for the input tags. The parameter passed to individual input methods
	 * (e.g. [[textInput()]]) will be merged with this property when rendering the input tag.
	 */
	public $inputOptions = array('class' => 'form-control');
	/**
	 * @var array the default options for the error tags. The parameter passed to [[error()]] will be
	 * merged with this property when rendering the error tag.
	 * The following special options are recognized:
	 *
	 * - tag: the tag name of the container element. Defaults to "div".
	 */
	public $errorOptions = array('class' => 'help-block');
	/**
	 * @var array the default options for the label tags. The parameter passed to [[label()]] will be
	 * merged with this property when rendering the label tag.
	 */
	public $labelOptions = array('class' => 'control-label');
	/**
	 * @var array the default options for the hint tags. The parameter passed to [[hint()]] will be
	 * merged with this property when rendering the hint tag.
	 * The following special options are recognized:
	 *
	 * - tag: the tag name of the container element. Defaults to "div".
	 */
	public $hintOptions = array('class' => 'hint-block');
	/**
	 * @var boolean whether to enable client-side data validation.
	 * If not set, it will take the value of [[ActiveForm::enableClientValidation]].
	 */
	public $enableClientValidation;
	/**
	 * @var boolean whether to enable AJAX-based data validation.
	 * If not set, it will take the value of [[ActiveForm::enableAjaxValidation]].
	 */
	public $enableAjaxValidation;
	/**
	 * @var boolean whether to perform validation when the input field loses focus and its value is found changed.
	 * If not set, it will take the value of [[ActiveForm::validateOnChange]].
	 */
	public $validateOnChange;
	/**
	 * @var boolean whether to perform validation while the user is typing in the input field.
	 * If not set, it will take the value of [[ActiveForm::validateOnType]].
	 * @see validationDelay
	 */
	public $validateOnType;
	/**
	 * @var integer number of milliseconds that the validation should be delayed when the input field
	 * is changed or the user types in the field.
	 * If not set, it will take the value of [[ActiveForm::validationDelay]].
	 */
	public $validationDelay;
	/**
	 * @var array the jQuery selectors for selecting the container, input and error tags.
	 * The array keys should be "container", "input", and/or "error", and the array values
	 * are the corresponding selectors. For example, `array('input' => '#my-input')`.
	 *
	 * The container selector is used under the context of the form, while the input and the error
	 * selectors are used under the context of the container.
	 *
	 * You normally do not need to set this property as the default selectors should work well for most cases.
	 */
	public $selectors;
	/**
	 * @var array different parts of the field (e.g. input, label). This will be used together with
	 * [[template]] to generate the final field HTML code. The keys are the token names in [[template]],
	 * while the values are the corresponding HTML code. Valid tokens include `{input}`, `{label}`,
	 * `{error}`, and `{error}`. Note that you normally don't need to access this property directly as
	 * it is maintained by various methods of this class.
	 */
	public $parts = array();


	/**
	 * PHP magic method that returns the string representation of this object.
	 * @return string the string representation of this object.
	 */
	public function __toString()
	{
		// __toString cannot throw exception
		// use trigger_error to bypass this limitation
		try {
			return $this->render();
		} catch (\Exception $e) {
			trigger_error($e->getMessage());
			return '';
		}
	}

	/**
	 * Renders the whole field.
	 * This method will generate the label, error tag, input tag and hint tag (if any), and
	 * assemble them into HTML according to [[template]].
	 * @param string|callable $content the content within the field container.
	 * If null (not set), the default methods will be called to generate the label, error tag and input tag,
	 * and use them as the content.
	 * If a callable, it will be called to generate the content. The signature of the callable should be:
	 *
	 * ~~~
	 * function ($field) {
	 *     return $html;
	 * }
	 * ~~~
	 *
	 * @return string the rendering result
	 */
	public function render($content = null)
	{
		if ($content === null) {
			if (!isset($this->parts['{input}'])) {
				$this->parts['{input}'] = Html::activeTextInput($this->model, $this->attribute, $this->inputOptions);
			}
			if (!isset($this->parts['{label}'])) {
				$this->parts['{label}'] = Html::activeLabel($this->model, $this->attribute, $this->labelOptions);
			}
			if (!isset($this->parts['{error}'])) {
				$this->parts['{error}'] = Html::error($this->model, $this->attribute, $this->errorOptions);
			}
			if (!isset($this->parts['{hint}'])) {
				$this->parts['{hint}'] = '';
			}
			$content = strtr($this->template, $this->parts);
		} elseif (!is_string($content)) {
			$content = call_user_func($content, $this);
		}
		return $this->begin() . "\n" . $content . "\n" . $this->end();
	}

	/**
	 * Renders the opening tag of the field container.
	 * @return string the rendering result.
	 */
	public function begin()
	{
		$clientOptions = $this->getClientOptions();
		if (!empty($clientOptions)) {
			$this->form->attributes[$this->attribute] = $clientOptions;
		}

		$inputID = Html::getInputId($this->model, $this->attribute);
		$attribute = Html::getAttributeName($this->attribute);
		$options = $this->options;
		$class = isset($options['class']) ? array($options['class']) : array();
		$class[] = "field-$inputID";
		if ($this->model->isAttributeRequired($attribute)) {
			$class[] = $this->form->requiredCssClass;
		}
		if ($this->model->hasErrors($attribute)) {
			$class[] = $this->form->errorCssClass;
		}
		$options['class'] = implode(' ', $class);
		$tag = ArrayHelper::remove($options, 'tag', 'div');

		return Html::beginTag($tag, $options);
	}

	/**
	 * Renders the closing tag of the field container.
	 * @return string the rendering result.
	 */
	public function end()
	{
		return Html::endTag(isset($this->options['tag']) ? $this->options['tag'] : 'div');
	}

	/**
	 * Generates a label tag for [[attribute]].
	 * The label text is the label associated with the attribute, obtained via [[Model::getAttributeLabel()]].
	 * @param array $options the tag options in terms of name-value pairs. It will be merged with [[labelOptions]].
	 * The options will be rendered as the attributes of the resulting tag. The values will be HTML-encoded
	 * using [[Html::encode()]]. If a value is null, the corresponding attribute will not be rendered.
	 *
	 * The following options are specially handled:
	 *
	 * - label: this specifies the label to be displayed. Note that this will NOT be [[encoded()]].
	 *   If this is not set, [[Model::getAttributeLabel()]] will be called to get the label for display
	 *   (after encoding).
	 *
	 * @return ActiveField the field object itself
	 */
	public function label($options = array())
	{
		$options = array_merge($this->labelOptions, $options);
		$this->parts['{label}'] = Html::activeLabel($this->model, $this->attribute, $options);
		return $this;
	}

	/**
	 * Generates a tag that contains the first validation error of [[attribute]].
	 * Note that even if there is no validation error, this method will still return an empty error tag.
	 * @param array $options the tag options in terms of name-value pairs. It will be merged with [[errorOptions]].
	 * The options will be rendered as the attributes of the resulting tag. The values will be HTML-encoded
	 * using [[Html::encode()]]. If a value is null, the corresponding attribute will not be rendered.
	 *
	 * The following options are specially handled:
	 *
	 * - tag: this specifies the tag name. If not set, "div" will be used.
	 *
	 * @return ActiveField the field object itself
	 */
	public function error($options = array())
	{
		$options = array_merge($this->errorOptions, $options);
		$this->parts['{error}'] = Html::error($this->model, $this->attribute, $options);
		return $this;
	}

	/**
	 * Renders the hint tag.
	 * @param string $content the hint content. It will NOT be HTML-encoded.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the hint tag. The values will be HTML-encoded using [[Html::encode()]].
	 *
	 * The following options are specially handled:
	 *
	 * - tag: this specifies the tag name. If not set, "div" will be used.
	 *
	 * @return ActiveField the field object itself
	 */
	public function hint($content, $options = array())
	{
		$options = array_merge($this->hintOptions, $options);
		$tag = ArrayHelper::remove($options, 'tag', 'div');
		$this->parts['{hint}'] = Html::tag($tag, $content, $options);
		return $this;
	}

	/**
	 * Renders an input tag.
	 * @param string $type the input type (e.g. 'text', 'password')
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[Html::encode()]].
	 * @return ActiveField the field object itself
	 */
	public function input($type, $options = array())
	{
		$options = array_merge($this->inputOptions, $options);
		$this->parts['{input}'] = Html::activeInput($type, $this->model, $this->attribute, $options);
		return $this;
	}

	/**
	 * Renders a text input.
	 * This method will generate the "name" and "value" tag attributes automatically for the model attribute
	 * unless they are explicitly specified in `$options`.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[Html::encode()]].
	 * @return ActiveField the field object itself
	 */
	public function textInput($options = array())
	{
		$options = array_merge($this->inputOptions, $options);
		$this->parts['{input}'] = Html::activeTextInput($this->model, $this->attribute, $options);
		return $this;
	}

	/**
	 * Renders a password input.
	 * This method will generate the "name" and "value" tag attributes automatically for the model attribute
	 * unless they are explicitly specified in `$options`.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[Html::encode()]].
	 * @return ActiveField the field object itself
	 */
	public function passwordInput($options = array())
	{
		$options = array_merge($this->inputOptions, $options);
		$this->parts['{input}'] = Html::activePasswordInput($this->model, $this->attribute, $options);
		return $this;
	}

	/**
	 * Renders a file input.
	 * This method will generate the "name" and "value" tag attributes automatically for the model attribute
	 * unless they are explicitly specified in `$options`.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[Html::encode()]].
	 * @return ActiveField the field object itself
	 */
	public function fileInput($options = array())
	{
		if ($this->inputOptions !== array('class' => 'form-control')) {
			$options = array_merge($this->inputOptions, $options);
		}
		$this->parts['{input}'] = Html::activeFileInput($this->model, $this->attribute, $options);
		return $this;
	}

	/**
	 * Renders a text area.
	 * The model attribute value will be used as the content in the textarea.
	 * @param array $options the tag options in terms of name-value pairs. These will be rendered as
	 * the attributes of the resulting tag. The values will be HTML-encoded using [[Html::encode()]].
	 * @return ActiveField the field object itself
	 */
	public function textarea($options = array())
	{
		$options = array_merge($this->inputOptions, $options);
		$this->parts['{input}'] = Html::activeTextarea($this->model, $this->attribute, $options);
		return $this;
	}

	/**
	 * Renders a radio button.
	 * This method will generate the "checked" tag attribute according to the model attribute value.
	 * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
	 *
	 * - uncheck: string, the value associated with the uncheck state of the radio button. If not set,
	 *   it will take the default value '0'. This method will render a hidden input so that if the radio button
	 *   is not checked and is submitted, the value of this attribute will still be submitted to the server
	 *   via the hidden input.
	 * - label: string, a label displayed next to the radio button.  It will NOT be HTML-encoded. Therefore you can pass
	 *   in HTML code such as an image tag. If this is is coming from end users, you should [[Html::encode()]] it to prevent XSS attacks.
	 *   When this option is specified, the radio button will be enclosed by a label tag.
	 * - labelOptions: array, the HTML attributes for the label tag. This is only used when the "label" option is specified.
	 *
	 * The rest of the options will be rendered as the attributes of the resulting tag. The values will
	 * be HTML-encoded using [[Html::encode()]]. If a value is null, the corresponding attribute will not be rendered.
	 * @param boolean $enclosedByLabel whether to enclose the radio within the label.
	 * If true, the method will still use [[template]] to layout the checkbox and the error message
	 * except that the radio is enclosed by the label tag.
	 * @return ActiveField the field object itself
	 */
	public function radio($options = array(), $enclosedByLabel = true)
	{
		$options = array_merge($this->inputOptions, $options);
		if ($enclosedByLabel) {
			if (!isset($options['label'])) {
				$options['label'] = Html::encode($this->model->getAttributeLabel($this->attribute));
			}
			$this->parts['{input}'] = Html::activeRadio($this->model, $this->attribute, $options);
			$this->parts['{label}'] = '';
		} else {
			$this->parts['{input}'] = Html::activeRadio($this->model, $this->attribute, $options);
		}
		return $this;
	}

	/**
	 * Renders a checkbox.
	 * This method will generate the "checked" tag attribute according to the model attribute value.
	 * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
	 *
	 * - uncheck: string, the value associated with the uncheck state of the radio button. If not set,
	 *   it will take the default value '0'. This method will render a hidden input so that if the radio button
	 *   is not checked and is submitted, the value of this attribute will still be submitted to the server
	 *   via the hidden input.
	 * - label: string, a label displayed next to the checkbox.  It will NOT be HTML-encoded. Therefore you can pass
	 *   in HTML code such as an image tag. If this is is coming from end users, you should [[Html::encode()]] it to prevent XSS attacks.
	 *   When this option is specified, the checkbox will be enclosed by a label tag.
	 * - labelOptions: array, the HTML attributes for the label tag. This is only used when the "label" option is specified.
	 *
	 * The rest of the options will be rendered as the attributes of the resulting tag. The values will
	 * be HTML-encoded using [[Html::encode()]]. If a value is null, the corresponding attribute will not be rendered.
	 * @param boolean $enclosedByLabel whether to enclose the checkbox within the label.
	 * If true, the method will still use [[template]] to layout the checkbox and the error message
	 * except that the checkbox is enclosed by the label tag.
	 * @return ActiveField the field object itself
	 */
	public function checkbox($options = array(), $enclosedByLabel = true)
	{
		if ($enclosedByLabel) {
			if (!isset($options['label'])) {
				$options['label'] = Html::encode($this->model->getAttributeLabel($this->attribute));
			}
			$this->parts['{input}'] = Html::activeCheckbox($this->model, $this->attribute, $options);
			$this->parts['{label}'] = '';
		} else {
			$this->parts['{input}'] = Html::activeCheckbox($this->model, $this->attribute, $options);
		}
		return $this;
	}

	/**
	 * Renders a drop-down list.
	 * The selection of the drop-down list is taken from the value of the model attribute.
	 * @param array $items the option data items. The array keys are option values, and the array values
	 * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
	 * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
	 * If you have a list of data models, you may convert them into the format described above using
	 * [[ArrayHelper::map()]].
	 *
	 * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
	 * the labels will also be HTML-encoded.
	 * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
	 *
	 * - prompt: string, a prompt text to be displayed as the first option;
	 * - options: array, the attributes for the select option tags. The array keys must be valid option values,
	 *   and the array values are the extra attributes for the corresponding option tags. For example,
	 *
	 * ~~~
	 * array(
	 *     'value1' => array('disabled' => true),
	 *     'value2' => array('label' => 'value 2'),
	 * );
	 * ~~~
	 *
	 * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of 'options',
	 *   except that the array keys represent the optgroup labels specified in $items.
	 *
	 * The rest of the options will be rendered as the attributes of the resulting tag. The values will
	 * be HTML-encoded using [[Html::encode()]]. If a value is null, the corresponding attribute will not be rendered.
	 *
	 * @return ActiveField the field object itself
	 */
	public function dropDownList($items, $options = array())
	{
		$options = array_merge($this->inputOptions, $options);
		$this->parts['{input}'] = Html::activeDropDownList($this->model, $this->attribute, $items, $options);
		return $this;
	}

	/**
	 * Renders a list box.
	 * The selection of the list box is taken from the value of the model attribute.
	 * @param array $items the option data items. The array keys are option values, and the array values
	 * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
	 * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
	 * If you have a list of data models, you may convert them into the format described above using
	 * [[\yii\helpers\ArrayHelper::map()]].
	 *
	 * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
	 * the labels will also be HTML-encoded.
	 * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
	 *
	 * - prompt: string, a prompt text to be displayed as the first option;
	 * - options: array, the attributes for the select option tags. The array keys must be valid option values,
	 *   and the array values are the extra attributes for the corresponding option tags. For example,
	 *
	 * ~~~
	 * array(
	 *     'value1' => array('disabled' => true),
	 *     'value2' => array('label' => 'value 2'),
	 * );
	 * ~~~
	 *
	 * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of 'options',
	 *   except that the array keys represent the optgroup labels specified in $items.
	 * - unselect: string, the value that will be submitted when no option is selected.
	 *   When this attribute is set, a hidden field will be generated so that if no option is selected in multiple
	 *   mode, we can still obtain the posted unselect value.
	 *
	 * The rest of the options will be rendered as the attributes of the resulting tag. The values will
	 * be HTML-encoded using [[Html::encode()]]. If a value is null, the corresponding attribute will not be rendered.
	 *
	 * @return ActiveField the field object itself
	 */
	public function listBox($items, $options = array())
	{
		$options = array_merge($this->inputOptions, $options);
		$this->parts['{input}'] = Html::activeListBox($this->model, $this->attribute, $items, $options);
		return $this;
	}

	/**
	 * Renders a list of checkboxes.
	 * A checkbox list allows multiple selection, like [[listBox()]].
	 * As a result, the corresponding submitted value is an array.
	 * The selection of the checkbox list is taken from the value of the model attribute.
	 * @param array $items the data item used to generate the checkboxes.
	 * The array keys are the labels, while the array values are the corresponding checkbox values.
	 * Note that the labels will NOT be HTML-encoded, while the values will.
	 * @param array $options options (name => config) for the checkbox list. The following options are specially handled:
	 *
	 * - unselect: string, the value that should be submitted when none of the checkboxes is selected.
	 *   By setting this option, a hidden input will be generated.
	 * - separator: string, the HTML code that separates items.
	 * - item: callable, a callback that can be used to customize the generation of the HTML code
	 *   corresponding to a single item in $items. The signature of this callback must be:
	 *
	 * ~~~
	 * function ($index, $label, $name, $checked, $value)
	 * ~~~
	 *
	 * where $index is the zero-based index of the checkbox in the whole list; $label
	 * is the label for the checkbox; and $name, $value and $checked represent the name,
	 * value and the checked status of the checkbox input.
	 * @return ActiveField the field object itself
	 */
	public function checkboxList($items, $options = array())
	{
		$this->parts['{input}'] = Html::activeCheckboxList($this->model, $this->attribute, $items, $options);
		return $this;
	}

	/**
	 * Renders a list of radio buttons.
	 * A radio button list is like a checkbox list, except that it only allows single selection.
	 * The selection of the radio buttons is taken from the value of the model attribute.
	 * @param array $items the data item used to generate the radio buttons.
	 * The array keys are the labels, while the array values are the corresponding radio button values.
	 * Note that the labels will NOT be HTML-encoded, while the values will.
	 * @param array $options options (name => config) for the radio button list. The following options are specially handled:
	 *
	 * - unselect: string, the value that should be submitted when none of the radio buttons is selected.
	 *   By setting this option, a hidden input will be generated.
	 * - separator: string, the HTML code that separates items.
	 * - item: callable, a callback that can be used to customize the generation of the HTML code
	 *   corresponding to a single item in $items. The signature of this callback must be:
	 *
	 * ~~~
	 * function ($index, $label, $name, $checked, $value)
	 * ~~~
	 *
	 * where $index is the zero-based index of the radio button in the whole list; $label
	 * is the label for the radio button; and $name, $value and $checked represent the name,
	 * value and the checked status of the radio button input.
	 * @return ActiveField the field object itself
	 */
	public function radioList($items, $options = array())
	{
		$this->parts['{input}'] = Html::activeRadioList($this->model, $this->attribute, $items, $options);
		return $this;
	}

	/**
	 * Renders a widget as the input of the field.
	 *
	 * Note that the widget must have both `model` and `attribute` properties. They will
	 * be initialized with [[model]] and [[attribute]] of this field, respectively.
	 *
	 * If you want to use a widget that does not have `model` and `attribute` properties,
	 * please use [[render()]] instead.
	 *
	 * @param string $class the widget class name
	 * @param array $config name-value pairs that will be used to initialize the widget
	 * @return ActiveField the field object itself
	 */
	public function widget($class, $config = array())
	{
		/** @var \yii\base\Widget $class */
		$config['model'] = $this->model;
		$config['attribute'] = $this->attribute;
		$config['view'] = $this->form->getView();
		$this->parts['{input}'] = $class::widget($config);
		return $this;
	}

	/**
	 * Returns the JS options for the field.
	 * @return array the JS options
	 */
	protected function getClientOptions()
	{
		$enableClientValidation = $this->enableClientValidation || $this->enableClientValidation === null && $this->form->enableClientValidation;
		if ($enableClientValidation) {
			$attribute = Html::getAttributeName($this->attribute);
			$validators = array();
			foreach ($this->model->getActiveValidators($attribute) as $validator) {
				/** @var \yii\validators\Validator $validator */
				$js = $validator->clientValidateAttribute($this->model, $attribute, $this->form->getView());
				if ($validator->enableClientValidation && $js != '') {
					$validators[] = $js;
				}
			}
			if (!empty($validators)) {
				$options['validate'] = new JsExpression("function(attribute, value, messages) {" . implode('', $validators) . '}');
			}
		}

		$enableAjaxValidation = $this->enableAjaxValidation || $this->enableAjaxValidation === null && $this->form->enableAjaxValidation;
		if ($enableAjaxValidation) {
			$options['enableAjaxValidation'] = 1;
		}

		if ($enableClientValidation && !empty($options['validate']) || $enableAjaxValidation) {
			$inputID = Html::getInputId($this->model, $this->attribute);
			$options['name'] = $inputID;
			foreach (array('validateOnChange', 'validateOnType', 'validationDelay') as $name) {
				$options[$name] = $this->$name === null ? $this->form->$name : $this->$name;
			}
			$options['container'] = isset($this->selectors['container']) ? $this->selectors['container'] : ".field-$inputID";
			$options['input'] = isset($this->selectors['input']) ? $this->selectors['input'] : "#$inputID";
			if (isset($this->errorOptions['class'])) {
				$options['error'] = '.' . implode('.', preg_split('/\s+/', $this->errorOptions['class'], -1, PREG_SPLIT_NO_EMPTY));
			} else {
				$options['error'] = isset($this->errorOptions['tag']) ? $this->errorOptions['tag'] : 'div';
			}
			return $options;
		} else {
			return array();
		}
	}
}
