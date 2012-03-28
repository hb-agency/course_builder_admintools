/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Winans Creative 2011, Helmut SchottmÙller 2009
 * @author     Blair Winans <blair@winanscreative.com>
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 * @author     Adam Fisher <adam@winanscreative.com>
 * @author     Includes code from survey_ce module from Helmut SchottmÙller <typolight@aurealis.de>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */
 
 
var CourseAttemptsWizard =
{
	/**
	 * Text wizard
	 * @param object
	 * @param string
	 * @param string
	 */
	courseattemptsWizard: function(el, command, id)
	{
		var table = $(id);
		var rows = table.getChildren().getChildren()[0];
		var parentTd = $(el).getParent();
		var parentTr = parentTd.getParent();
		var cols = parentTr.getChildren();

		Backend.getScrollOffset();

		switch (command)
		{
			case 'new':
				var clone = parentTr.clone(true).injectAfter(parentTr);
				clone.getFirst().getFirst().value = "";
				clone.getFirst().getNext().getFirst().value = "";
				break;
				
			case 'copy':
				var clone = parentTr.clone(true).injectAfter(parentTr);
				clone.getFirst().getFirst().value = parentTr.getFirst().getFirst().value;
				clone.getFirst().getNext().getFirst().value = parentTr.getFirst().getNext().getFirst().value;
				break;

			case 'up':
				if (parentTr.getPrevious()) 
				{
					parentTr.injectBefore(parentTr.getPrevious());
				}
				break;

			case 'down':
				if (parentTr.getNext())
				{
					parentTr.injectAfter(parentTr.getNext());
				}
				break;

			case 'delete':
				(rows.length > 1) ? parentTr.dispose() : null;
				break;
		}

		// renumber cid parameter
		rows = table.getChildren().getChildren();
		for (i = 0; i < rows[0].length; i++)
		{
			row = rows[0][i];
			a = row.getFirst().getNext().getNext().getFirst();
			a.href = a.href.replace(/cid\=[0-9]+/ig, "cid="+i);
		}

		if (clone)
		{
			clone.getFirst().getFirst().select();
		}
	}
};