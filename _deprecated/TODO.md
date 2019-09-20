# buildReplaceQueryMVA()

```
$new_dataset = array_diff(array_flip($dataset), $mva_atrributes);
```

вместо 
```
$new_dataset = array_filter($dataset, function ($value, $key) use ($mva_atrributes) {
            return !in_array($key, $mva_atrributes);
        }, ARRAY_FILTER_USE_BOTH);
```

? 

# TODO: Sphinx 

http://sphinxsearch.com/docs/manual-2.3.2.html#sphinxql-truncate-rtindex
http://sphinxsearch.com/docs/manual-2.3.2.html#sphinxql-flush-ramchunk
http://sphinxsearch.com/docs/manual-2.3.2.html#sphinxql-flush-rtindex
http://sphinxsearch.com/docs/manual-2.3.2.html#sphinxql-show-tables
http://sphinxsearch.com/docs/manual-2.3.2.html#sphinxql-describe
http://sphinxsearch.com/docs/manual-2.3.2.html#sphinxql-show-meta


