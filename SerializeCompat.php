<?php
    /**
     * Serialization compatibility trait compatible with:
     * - PHP < 7.4: uses __sleep/__wakeup (legacy payload)
     * - PHP >= 7.4: uses __serialize/__unserialize (new payload)
     *
     * Guidelines:
     * - Reconstruction logic goes in initAfterUnserialize() (override in classes)
     * - The list of serializable properties is determined via Reflection and
     *   includes ALL non-static (public/protected/private, even inherited).
     * - Reading values: (array)$this to avoid visibility and typed uninitialized issues
     * - Writing non-public values: Closure bound to the declaring class
     */
    trait SerializeCompat
    {
        /**
         * Hook: reconstruct derived state, validations, etc.
         * Override in classes that use the trait.
         */
        protected function initAfterUnserialize()
        {
            // Default: no action
        }

        /**
         * List of NON-static serializable properties (ReflectionProperty[])
         */
        protected function listSerializableReflectionProps()
        {
            $ref = new \ReflectionClass($this);
            $props = $ref->getProperties();
            $out = [];
            foreach ($props as $p) {
                if ($p->isStatic()) {
                    continue;
                }
                $out[] = $p;
            }
            return $out;
        }

        /**
         * Map property name → current value, obtained via (array)$this
         * Supports "mangled" keys for private/protected.
         */
        protected function readAllPropertyValues()
        {
            $raw = (array) $this; // also includes private/protected with mangled keys
            $map = [];

            // Build a lookup for mangled keys -> value
            // - public: 'prop'
            // - protected: "\0*\0prop"
            // - private: "\0DeclaringClass\0prop"
            foreach ($this->listSerializableReflectionProps() as $prop) {
                $name = $prop->getName();

                // public
                if ($prop->isPublic()) {
                    if (array_key_exists($name, $raw)) {
                        $map[$name] = $raw[$name];
                    }
                    continue;
                }

                // protected
                $protKey = "\0*\0" . $name;
                if ($prop->isProtected() && array_key_exists($protKey, $raw)) {
                    $map[$name] = $raw[$protKey];
                    continue;
                }

                // private
                $declaring = $prop->getDeclaringClass()->getName();
                $privKey = "\0" . $declaring . "\0" . $name;
                if (array_key_exists($privKey, $raw)) {
                    $map[$name] = $raw[$privKey];
                }
            }
            return $map;
        }

        /**
         * Sets a property, respecting visibility.
         * For non-public uses a Closure bound to the declaring class.
         */
        protected function setPropertyValue($name, $value, \ReflectionProperty $prop)
        {
            if ($prop->isPublic()) {
                $this->$name = $value;
                return;
            }

            // Bound setter without ReflectionProperty::setAccessible (avoids deprecations)
            $declaring = $prop->getDeclaringClass()->getName();
            $setter = \Closure::bind(function ($obj, $n, $v) {
                $obj->$n = $v;
            }, null, $declaring);

            $setter($this, $name, $value);
        }

        /**
         * __serialize: "new" payload for PHP >= 7.4
         */
        public function __serialize()
        {
            $values = $this->readAllPropertyValues(); // name -> value
            $out = [];

            foreach ($this->listSerializableReflectionProps() as $prop) {
                $name = $prop->getName();
                if (array_key_exists($name, $values)) {
                    $out[$name] = $values[$name];
                }
                // If a typed property is uninitialized, it won't appear in $values → OK
            }

            return $out;
        }

        /**
         * __unserialize: reconstructs properties from the "new" payload
         */
        public function __unserialize(array $data)
        {
            foreach ($this->listSerializableReflectionProps() as $prop) {
                $name = $prop->getName();
                if (array_key_exists($name, $data)) {
                    $this->setPropertyValue($name, $data[$name], $prop);
                }
            }
            $this->initAfterUnserialize();
        }

        /**
         * __sleep: for PHP < 7.4 (legacy payload)
         */
        public function __sleep()
        {
            $names = [];
            foreach ($this->listSerializableReflectionProps() as $prop) {
                $names[] = $prop->getName();
            }
            return $names;
        }

        /**
         * __wakeup: wrapper for legacy payload. Deprecated in 8.5,
         * but still needed for compatibility with old strings and PHP < 7.4.
         */
        public function __wakeup()
        {
            $this->initAfterUnserialize();
        }
    }
