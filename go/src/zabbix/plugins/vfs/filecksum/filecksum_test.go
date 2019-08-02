/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

package filecksum

import (
	"reflect"
	"testing"
	"zabbix/pkg/std"
)

var CrcFile = "1234"

func TestUptime(t *testing.T) {
	stdOs = std.NewMockOs()

	stdOs.(std.MockOs).MockFile("text.txt", []byte(CrcFile))
	if result, err := impl.Export("vfs.file.cksum", []string{"text.txt"}); err != nil {
		t.Errorf("vfs.file.cksum returned error %s", err.Error())
	} else {
		if crc, ok := result.(uint32); !ok {
			t.Errorf("vfs.file.cksum returned unexpected value type %s", reflect.TypeOf(result).Kind())
		} else {
			if crc != 3582362371 {
				t.Errorf("vfs.file.cksum returned invalid result")
			}
		}
	}
}
