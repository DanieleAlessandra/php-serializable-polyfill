<?php
    require_once __DIR__ . '/SerializeCompat.php'; // If the trait is in a separate file
    // Alternatively: paste the trait directly above here and remove this line.

    class Demo
    {
        use SerializeCompat;

        // Public / Protected / Private
        public $id;
        protected $name;
        private $flags;

        // Derived state / non-persistent cache
        private $computed;

        public function __construct($id = null, $name = null, $flags = [])
        {
            $this->id = $id;
            $this->name = $name;
            $this->flags = $flags;
            $this->computed = null;
        }

        protected function initAfterUnserialize()
        {
            // Rebuild any “transient” state
            $this->computed = ($this->name !== null ? strtoupper($this->name) : null);
        }

        // Convenient debug methods to inspect state
        public function debugState($label)
        {
            echo "=== {$label} ===\n";
            echo "id: " . var_export($this->id, true) . "\n";
            echo "name: " . var_export($this->getName(), true) . "\n";
            echo "flags: " . var_export($this->getFlags(), true) . "\n";
            echo "computed (PRIVATE): " . var_export($this->getComputed(), true) . "\n";
            echo "\n";
        }

        public function getName()
        {
            // Accessor for protected
            return $this->name;
        }

        public function getFlags()
        {
            // Accessor for private
            return $this->flags;
        }

        public function getComputed()
        {
            return $this->computed;
        }
    }

    // ---- TEST ----

    echo "PHP version: " . PHP_VERSION . "\n\n";

    $demo = new Demo(123, 'freemius', ['beta' => true]);
    $demo->debugState('Before serialize');

    $payload = serialize($demo);
    echo "Serialized length: " . strlen($payload) . "\n";
    echo "Serialized sample: " . substr($payload, 0, 80) . "...\n\n";

    $copy = unserialize($payload);
    $copy->debugState('After unserialize');

    // (Optional) Verify multiple round-trips
    $payload2 = serialize($copy);
    $copy2 = unserialize($payload2);
    $copy2->debugState('After re-unserialize');

    $payload3 = 'O:4:"Demo":5:{s:2:"id";i:123;s:4:"name";s:8:"freemius";s:5:"flags";a:1:{s:4:"beta";b:1;}s:8:"computed";N;s:5:"error";s:19:"Should not see this";}';
    $copy3 = unserialize($payload3);
    $copy3->debugState('After re-unserialize (from string)');

    echo "Done.\n";
