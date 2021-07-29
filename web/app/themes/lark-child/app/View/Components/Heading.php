<?php

namespace App\View\Components;

use Roots\Acorn\View\Component;

class Heading extends Component
{
    /**
     * @var string
     */
    public $size;

    /**
     * @var array
     */
    public $sizeOptions;

    /**
     * @var string
     */
    public $alignment;

    /**
     * @var string
     */
    public $alignmentClasses;

    /**
     * @var string
     */
    public $additionalClasses;

    /**
     * @var string
     */
    public $colour;

    /**
     * @var string
     */
    public $default = 'h2';

    /**
     * Create the component instance.
     *
     * @param  string  $type
     * @param  string  $message
     * @return void
     */
    public function __construct(
        string $size,
        array $sizeOptions = [],
        string $alignment = 'left',
        string $alignmentClasses = '',
        string $additionalClasses = '',
        string $colour = '',
        string $default = 'h2'
    ) {
        $this->size = $size;
        $this->sizeOptions = $sizeOptions;
        $this->alignment = $alignment;
        $this->alignmentClasses = $alignmentClasses;
        $this->additionalClasses = $additionalClasses;
        $this->colour = $colour;
        $this->default = $default;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        $classes = collect([
            $this->headingClasses(),
            $this->headingAlignment(),
            $this->additionalClasses(),
            $this->headingColour(),
        ]);

        return $this->view('components.heading', [
            'size' => $this->size,
            'classes' => $classes->filter()->implode(' '),
        ]);
    }

    public function headingClasses()
    {
        // If the admin doesn't select an actual size i.e. the below,
        // then we should use the default
        if (in_array($this->size, ['default', '', null])) {
            $this->size = $this->default;
        }

        return headingSize($this->size, $this->sizeOptions);
    }

    public function additionalClasses()
    {
        return ! empty($this->additionalClasses) ? $this->additionalClasses : '';
    }

    public function headingColour()
    {
        if (empty($this->colour) || $this->colour == 'default') {
            return '';
        }

        return "text-{$this->colour}";
    }

    public function headingAlignment()
    {
        $default = 'text-left mr-auto';

        if ( ! empty($this->alignmentClasses)) {
            return $this->alignmentClasses;
        }

        switch ($this->alignment) {
          case 'left':
            $alignmentClasses = $default;

            break;
          case 'right':
            $alignmentClasses = 'text-right ml-auto';

            break;
          case 'center':
            $alignmentClasses = 'text-center mx-auto';

            break;
          default:
            // LEFT
            $alignmentClasses = 'text-left mr-auto';

            break;
        }

        return $alignmentClasses;
    }
}
