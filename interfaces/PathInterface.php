<?php

namespace Arris;

interface PathInterface
{
    /**
     * The character used to separate path atoms.
     */
    public const ATOM_SEPARATOR = '/';
    
    /**
     * The character used to separate path name atoms.
     */
    public const EXTENSION_SEPARATOR = '.';
    
    /**
     * The atom used to represent 'parent'.
     */
    const PARENT_ATOM = '..';
    
    /**
     * The atom used to represent 'self'.
     */
    public const SELF_ATOM = '.';
    
    public function __construct($path, $isAbsolutePath = null, $hasTrailingSeparator = null);
    
    public function validateAtom($value);

    public function toString($hasTrailingSeparator = false);
    public function __toString();

    public function setAbsolutePath($is_present = true);
    public function setTrailingSeparator($is_present = true);
    public function setOptions($options = []);
    
    public static function create($path, $isAbsolutePath = null, $hasTrailingSeparator = null);
    public function join($data);
    public function joinName($data);
    
    public function isPresent():bool;
    public function makePath($access_rights = 0777):bool;
}

# -eof-
