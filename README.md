jisho.org Workflow for Alfred v2
=====================

Alfred v2 workflow for searching beta.jisho.org.

![Alt text](https://dl.dropboxusercontent.com/u/3781820/alfred-jisho-workflow.png?raw=true)

The keyword is `jishos` and can be used as follows.

`jishos {query}`

Clicking an entry will copy it to your clipboard. If you shift-click an entry it will open the words page on beta.jisho.org.

##Commands
The workflow is also compatible with the following beta.jisho.org search modifiers. 

`#common`
`#jlpt-n1`
`#jlpt-n2`
`#jlpt-n3`
`#jlpt-n4`
`#noun`
`#verb`
`#adjective`

These can be added to the end of your search query. For example, to return words that are common nouns you can do the following.

`jishos {query} #common #noun`

Query results are now cached to improve performance. To remove the cached queries you can use the following command.

`jisho clear`

###[DOWNLOAD](https://github.com/Jeemusu/alfred-jisho-workflow/blob/master/jisho.org.alfredworkflow?raw=true)


##TODO
- Add compatibility for sentance results.