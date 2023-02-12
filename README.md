# Collections Plus

## Description

Plugin for Omeka Classic. Adds new functions to the Collection browsing admin interface, and also the possibility to further customize single Collections.

Among other things, the plugin allows to:

- choose a custom theme for each Collection;
- choose a custom number of Items to be shown per page when browsing a Collection;
- choose a custom sort field and order for Items when browsing a Collection;
- choose a custom sort field and order when browsing Collections.

## Installation
Uncompress files and rename plugin folder "CollectionsPlus".

Then install it like any other Omeka plugin.

### Please note

It would probably be a good idea to add whichever sort field you select to sort by to the list of sort links in your browse views. Take a look at the Omeka default theme for an example of how this can be done in `Items/Browse` file:

	$sortLinks[__('Title')] = 'Dublin Core,Title';
	$sortLinks[__('Creator')] = 'Dublin Core,Creator';
	$sortLinks[__('Date Added')] = 'added';
	// begin added code
	if (get_option('defaultsort_items_enabled')) {
		$newSortField = get_option('defaultsort_items_option');
		if (!in_array($newSortField, array('Dublin Core,Title','Dublin Core,Creator','added'))) {
			$array = explode(',', $newSortField);
			$sortLinks[__($array[1])] = $newSortField;
		}
	}
	// end added code

## Warning
Use it at your own risk.

It’s always recommended to backup your files and your databases and to check your archives regularly so you can roll back if needed.

## Troubleshooting
See online issues on the <a href="https://github.com/DBinaghi/plugin-CollectionsPlus/issues" target="_blank">plugin issues</a> page on GitHub.


## License
This plugin is published under the <a href="https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html" target="_blank">CeCILL v2.1</a> licence, compatible with <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPL</a> and approved by <a href="https://www.fsf.org/" target="_blank">FSF</a> and <a href="http://opensource.org/" target="_blank">OSI</a>.

In consideration of access to the source code and the rights to copy, modify and redistribute granted by the license, users are provided only with a limited warranty and the software’s author, the holder of the economic rights, and the successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or developing or reproducing the software by the user are brought to the user’s attention, given its Free Software status, which may make it complicated to use, with the result that its use is reserved for developers and experienced professionals having in-depth computer knowledge. Users are therefore encouraged to load and test the suitability of the software as regards their requirements in conditions enabling the security of their systems and/or data to be ensured and, more generally, to use and operate it in the same conditions of security. This Agreement may be freely reproduced and published, provided it is not altered, and that no provisions are either added or removed herefrom.

## Acknowledgments
Part of this plugin was inspired by the [Enhanced Collections](https://github.com/BGSU-LITS/Enhanced-Collections-Plugin) plugin created in 2013 by the team of [Bowling Green State University Libraries](http://ul2.bgsu.edu/labs/).

## Copyright
Copyright [Daniele Binaghi](https://github.com/DBinaghi), 2023
Copyright [Bowling Green State University Libraries](http://ul2.bgsu.edu/labs/), 2013
