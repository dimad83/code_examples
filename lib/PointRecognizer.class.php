<?php

/**
 * Класс для опознания по текстовому названию скважины или другого объекта на месторождении
 * 
 */
class PointRecognizer {
	private $wells_identifiable_by_name = [];
	private $wells_identifiable_by_int_name = [];
	private $wells_non_identifiable_by_int_name = [];
	private $surface_objects_list_by_name = [];
	
	public function __construct() {
		// в фильтре null, потому что здесь нам нужны даже закрытые скважины
		$this->wells_identifiable_by_name = get_hash((new Wells())->getList(null), 'name', 'id');

		$this->surface_objects_list_by_name = get_hash((new Surface_Objects())->getList(), 'name', 'id');
		// собираем информацию по скважинам, переводя их имена в число; группируем "повторы"
		foreach($this->wells_identifiable_by_name as $name=>$well_id) {
			$int_name = intval($name);
			if (isset($this->wells_identifiable_by_int_name[$int_name])) {
				unset($this->wells_identifiable_by_int_name[$int_name]['id']); // удаляем id, т.к. его не получилось однозначно идентифицировать
				$this->wells_identifiable_by_int_name[$int_name]['parent_well_names'][] = $name;
			}
			else {
				$this->wells_identifiable_by_int_name[$int_name]['id'] = $well_id;
				$this->wells_identifiable_by_int_name[$int_name]['parent_well_names'][] = $name;
			}
		}
		// перебрасываем скважины, которые нельзя однозначно идентифицировать по числовой части названия, в список близнецов
		foreach($this->wells_identifiable_by_int_name as $int_name=>$well) {
			if (count($well['parent_well_names'])>1) {
				$this->wells_non_identifiable_by_int_name[$int_name] = $well;
				unset($this->wells_identifiable_by_int_name[$int_name]);
			}
		}
	}

	/**
	 * Try to find well_id and subsurface_id if it is possible
	 * @param string $rough_name original well name from parsing file
	 * @return array well_id, surface_object_id, hint
	 */
	// TODO заменить везде этот метод на getPointRecognized, иначе возможны расхождения в распознвании, если правила распознавания изменятся
	public function getWellOrSurfaceObject($rough_name)
	{
		$rough_name = trim($rough_name);
		$surface_object_name = $rough_name;
		$well_name = str_replace(['б', 'Б', 'Р', 'Н'], ['b', 'b', '', 'H'], $rough_name); // замены русских букв на английские
		$int_well_name = intval($well_name);

		unset($rough_name); // уничтожаем во избежание дальнейшего ошибочного использования

		// сначала ищем объект по полному совпадениею $rough_name и имени объекта (список скважин и список поверхностных объектов)
		@$well_id = $this->wells_identifiable_by_name[$well_name];
		@$surface_object_id = $this->surface_objects_list_by_name[$surface_object_name];

		if (!$surface_object_id && !$well_id) {
            // если скважину не нашли, то ищем по совпадению в однозначно опознаваемых по цифрам скважинах
            if (!$well_id) {
                @$well_id = $this->wells_identifiable_by_int_name[$int_well_name]['id'];
            }
            // если все равно не нашли, то ищем подсказку для пользователя в списке двояко опознаваемых по числу скважин
            if (!$well_id && isset($this->wells_non_identifiable_by_int_name[$int_well_name])) {
                @$hint = "Найдено несколько похожих скважин: '" . join("', '", $this->wells_non_identifiable_by_int_name[$int_well_name]['parent_well_names']) . "'. Пожалуйста, уточните точное наименование, указав его в файле.";
            }
            // снова не нашли скважину - вернем про нее null
        }
		return [
			'well_id' => ($well_id ?: null),
			'surface_object_id' => ($surface_object_id ?: null),
			'hint'=>(@$hint ?: '')
		];
	}

	/*
	 * Очень похож на getWellOrSurfaceObject, но делает больше работы во избежание повторения кода в разных методах
	 * Важно: возвращает объет типа Point
	 */
	public function getPointRecognized($point_name)
	{
		$point = new Point();
		$point_name = trim($point_name);
		if (empty($point_name)) {
			$point->error = "точка замера не задана";
			return $point;
		}

		$surface_object_name = $point_name;
		$well_name = str_replace(['б', 'Б', 'Р', 'Н'], ['b', 'b', '', 'H'], $point_name); // замены русских букв на английские
		$int_well_name = intval($well_name);
		// сначала ищем объект по полному совпадениею $rough_name и имени объекта (список скважин и список поверхностных объектов)
		$point->well_id = $this->wells_identifiable_by_name[$well_name] ?? null;
		$point->surface_object_id = $this->surface_objects_list_by_name[$surface_object_name] ?? null;

		if (!$point->surface_object_id && !$point->well_id) {
			// если скважину не нашли, то ищем по совпадению в однозначно опознаваемых по цифрам скважинах
			if (!$point->well_id) {
				$point->well_id = $this->wells_identifiable_by_int_name[$int_well_name]['id'] ?? null;
				if ($point->well_id) return $point;
			}
			// если все равно не нашли, то ищем подсказку для пользователя в списке двояко опознаваемых по числу скважин
			if (!$point->well_id && isset($this->wells_non_identifiable_by_int_name[$int_well_name])) {
				$point->error = "'$point_name' - найдено несколько похожих скважин: '" . join("', '", $this->wells_non_identifiable_by_int_name[$int_well_name]['parent_well_names']) . "'. Пожалуйста, уточните точное наименование, указав его в файле.";
				return $point;
			}
			$point->error = "'$point_name' - точка замера не распознана ни как <a href='/index.php?module=Wells' target='_blank'>скважина</a>, ни как <a href='/index.php?module=Surface_Objects' target='_blank'>наземный объект</a>";
			return $point;

		}
		else if ($point->well_id && $point->surface_object_id) {
			$point->error = "'$point_name' - распознана и как скважина и как объект. Обратитесь к разработчику для решения проблемы.";
			return $point;
		}
		return $point;
	}

    /**
     * Возвращает список скважин и surface_objects
     * @return array 'id'=>('well_'.$id | 'sobject_'.$id), 'name'=>$name, 'pad_name'=>$pad_name
     */
    public function getList($add_only_nulls = false) {
        $points = [];
        if ($add_only_nulls) {
            $points[] = ['id'=>"onlynulls_true", 'name'=>'(без скважины/объекта)'];
        }
        $wells = (new Wells())->getList();
        foreach ($wells as $v) {
            $points[] = ['id'=>"well_{$v['id']}", 'name'=>$v['name'], 'pad_name'=>$v['pad_name']];
        }
        $surface_objects = (new Surface_Objects())->getList();
        foreach ($surface_objects as $v) {
            $points[] = ['id'=>"sobject_{$v['id']}", 'name'=>$v['name'], 'pad_name'=>$v['pad_name']];
        }
        return $points;
    }

    public function parsePoint($point){
        list($type, $id) = array_pad(explode('_', $point), 2, null); // array_pad нужен, когда point равен ""
        return [
            'well_id' => ('well'==$type ? $id : null),
            'surface_object_id' => ('sobject'==$type ? $id : null),
            'only_nulls' => ('onlynulls'==$type ? true : null),
        ];
    }
}
