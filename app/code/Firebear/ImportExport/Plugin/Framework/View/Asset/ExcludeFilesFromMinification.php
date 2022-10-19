<?php

namespace Firebear\ImportExport\Plugin\Framework\View\Asset;

use Magento\Framework\View\Asset\Minification;

/**
 * Class ExcludeFilesFromMinification
 *
 * @package Firebear\ImportExport\Plugin\Framework\View\Asset
 */
class ExcludeFilesFromMinification
{
    public function aroundGetExcludes(Minification $subject, callable $proceed, $contentType)
    {
        $result = $proceed($contentType);
        if ($contentType != 'js') {
            return $result;
        }

        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/abap';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/abc';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/actionscript';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/ada';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/apache_conf';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/applescript';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/asciidoc';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/asl';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/assembly_x86';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/autohotkey';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/batchfile';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/bro';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/c_cpp';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/c9search';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/cirru';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/clojure';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/cobol';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/coffee';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/coldfusion';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/csharp';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/csound_document';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/csound_orchestra';
        $result[] = 'Firebear_ImportExport/js/lib/ace/snippets/csound_score';

        $result[] = 'Firebear_ImportExport/js/lib/ace/ace';
        $result[] = 'Firebear_ImportExport/js/lib/ace/ext-beautify';
        $result[] = 'Firebear_ImportExport/js/lib/ace/ext-elastic_tabstops_lite';
        $result[] = 'Firebear_ImportExport/js/lib/ace/ext-emmet';

        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-abap';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-abc';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-actionscript';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-ada';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-apache_conf';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-applescript';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-asciidoc';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-asl';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-assembly_x86';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-autohotkey';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-batchfile';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-bro';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-c_cpp';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-c9search';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-cirru';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-clojure';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-cobol';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-coffee';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-coldfusion';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-csharp';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-csound_document';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-csound_orchestra';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-csound_score';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-css';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-csp';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-curly';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-d';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-dart';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-diff';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-django';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-dockerfile';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-dot';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-drools';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-edifact';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-eiffel';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-ejs';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-elixir';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-elm';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-erlang';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-forth';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-fortran';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-ftl';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-gcode';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-gherkin';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-gitignore';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-glsl';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-gobstones';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-golang';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-graphqlschema';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-groovy';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-haml';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-handlebars';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-haskell_cabal';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-haskell';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-haxe';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-hjson';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-html_elixir';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-html_ruby';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-html';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-ini';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-io';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-jack';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-jade';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-java';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-javascript';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-json';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-jsoniq';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-jsp';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-jssm';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-jsx';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-julia';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-kotlin';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-latex';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-less';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-liquid';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-lisp';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-livescript';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-lsl';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-lua';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-luapage';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-lucene';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-makefile';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-markdown';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-mask';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-matlab';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-maze';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-mel';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-mixal';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-mushcode';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-mysql';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-nix';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-nsis';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-objectivec';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-ocaml';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-pascal';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-perl';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-pgsql';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-php';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-pig';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-plain_text';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-powershell';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-praat';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-prolog';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-properties';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-protobuf';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-python';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-r';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-razor';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-rdoc';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-red';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-redshift';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-rhtml';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-rst';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-ruby';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-sass';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-scad';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-scala';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-scheme';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-scss';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-sh';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-sjs';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-smarty';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-snippets';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-soy_template';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-space';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-sparql';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-sql';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-sqlserver';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-stylus';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-svg';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-swift';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-tcl';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-tex';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-text';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-textile';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-toml';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-tsx';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-turtle';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-twig';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-typescript';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-vala';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-vbscript';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-velocity';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-verilog';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-vhdl';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-wollok';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-xml';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-xquery';
        $result[] = 'Firebear_ImportExport/js/lib/ace/mode-yaml';

        $result[] = 'Firebear_ImportExport/js/lib/ace/worker-xquery';
        $result[] = 'Firebear_ImportExport/js/lib/ace/worker-xml';
        $result[] = 'Firebear_ImportExport/js/lib/ace/worker-php';
        $result[] = 'Firebear_ImportExport/js/lib/ace/worker-lua';
        $result[] = 'Firebear_ImportExport/js/lib/ace/worker-json';
        $result[] = 'Firebear_ImportExport/js/lib/ace/worker-javascript';
        $result[] = 'Firebear_ImportExport/js/lib/ace/worker-html';
        $result[] = 'Firebear_ImportExport/js/lib/ace/worker-css';
        $result[] = 'Firebear_ImportExport/js/lib/ace/worker-coffee';

        return $result;
    }
}
