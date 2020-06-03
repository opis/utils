<?php

namespace Opis\Utils\Test;

use Opis\Utils\Uri;
use PHPUnit\Framework\TestCase;

class UriMergeTest extends TestCase
{
    /**
     * @dataProvider uriProvider
     */
    public function testMerge(Uri $base, string $ref, string $result)
    {
        $this->assertEquals($result, (string)$base->resolveRef($ref));
    }

    public function uriProvider()
    {
        $base = Uri::create('http://a/b/c/d;p?q');

        $data = [
            // Normal Examples

            ["g:h", "g:h"],
            ["g", "http://a/b/c/g"],
            ["./g", "http://a/b/c/g"],
            ["g/", "http://a/b/c/g/"],
            ["/g", "http://a/g"],
            ["//g", "http://g"],
            ["?y", "http://a/b/c/d;p?y"],
            ["g?y", "http://a/b/c/g?y"],
            ["#s", "http://a/b/c/d;p?q#s"],
            ["g#s", "http://a/b/c/g#s"],
            ["g?y#s", "http://a/b/c/g?y#s"],
            [";x", "http://a/b/c/;x"],
            ["g;x", "http://a/b/c/g;x"],
            ["g;x?y#s", "http://a/b/c/g;x?y#s"],
            ["", "http://a/b/c/d;p?q"],
            [".", "http://a/b/c/"],
            ["./", "http://a/b/c/"],
            ["..", "http://a/b/"],
            ["../", "http://a/b/"],
            ["../g", "http://a/b/g"],
            ["../..", "http://a/"],
            ["../../", "http://a/"],
            ["../../g", "http://a/g"],

            // Abnormal Examples

            /*
             Parsers must be careful in handling cases where there are more ".."
             segments in a relative-path reference than there are hierarchical
             levels in the base URI's path.  Note that the ".." syntax cannot be
             used to change the authority component of a URI.
             */

            ["../../../g", "http://a/g"],
            ["../../../../g", "http://a/g"],

            /*
             Similarly, parsers must remove the dot-segments "." and ".." when
             they are complete components of a path, but not when they are only
             part of a segment.
             */

            ["/./g", "http://a/g"],
            ["/../g", "http://a/g"],
            ["g.", "http://a/b/c/g."],
            [".g", "http://a/b/c/.g"],
            ["g..", "http://a/b/c/g.."],
            ["..g", "http://a/b/c/..g"],

            /*
             Less likely are cases where the relative reference uses unnecessary
             or nonsensical forms of the "." and ".." complete path segments.
             */

            ["./../g", "http://a/b/g"],
            ["./g/.", "http://a/b/c/g/"],
            ["g/./h", "http://a/b/c/g/h"],
            ["g/../h", "http://a/b/c/h"],
            ["g;x=1/./y", "http://a/b/c/g;x=1/y"],
            ["g;x=1/../y", "http://a/b/c/y"],

            /*
             Some applications fail to separate the reference's query and/or
             fragment components from the path component before merging it with
             the base path and removing dot-segments.  This error is rarely
             noticed, as typical usage of a fragment never includes the hierarchy
             ("/") character and the query component is not normally used within
             relative references.
             */

            ["g?y/./x", "http://a/b/c/g?y/./x"],
            ["g?y/../x", "http://a/b/c/g?y/../x"],
            ["g#s/./x", "http://a/b/c/g#s/./x"],
            ["g#s/../x", "http://a/b/c/g#s/../x"],
        ];

        return array_map(function (array $value) use ($base) {
            array_unshift($value, $base);
            return $value;
        }, $data);
    }
}