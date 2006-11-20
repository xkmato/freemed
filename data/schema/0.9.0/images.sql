# $Id$
#
# Authors:
#      Jeff Buchbinder <jeff@freemedsoftware.org>
#
# FreeMED Electronic Medical Record and Practice Management System
# Copyright (C) 1999-2006 FreeMED Software Foundation
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

CREATE TABLE `images` (
	imagedt			DATE,
	imagepat		INT UNSIGNED NOT NULL DEFAULT 0,
	imagetype		VARCHAR (50),
	imagecat		VARCHAR (50),
	imagedesc		VARCHAR (150),
	imageeoc		TEXT,
	imagefile		VARCHAR (100),
	imageformat		CHAR (4) NOT NULL DEFAULT 'djvu',
	imagephy		INT UNSIGNED DEFAULT 0,
	imagereviewed		INT UNSIGNED DEFAULT 0,
	locked			INT UNSIGNED DEFAULT 0,
	id			INT UNSIGNED NOT NULL AUTO_INCREMENT,
	PRIMARY KEY 		( id ),

	#	Define keys

	KEY			( imagepat, imagetype, imagecat, imagedt )
);

