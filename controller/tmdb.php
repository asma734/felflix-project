<?php
$tmdb_api_key = "19ec8eebb867ed533ce9bde4c160b437";
$tmdb_base    = "https://api.themoviedb.org/3";

function tmdbGet($url){
    if (defined('PHPUNIT_TEST_SUITE') && isset($GLOBALS['mock_tmdb_responses'])) {
        foreach ($GLOBALS['mock_tmdb_responses'] as $pattern => $response) {
            if (str_contains($url, $pattern)) {
                return $response;
            }
        }
    }
    $ctx=stream_context_create(['http'=>['timeout'=>10,'ignore_errors'=>true]]);
    $d=@file_get_contents($url,false,$ctx);
    return $d?(json_decode($d,true)??[]):[];
}
// Films populaires
function tmdbMovies($page=1,$lang='fr-FR'){
    global $tmdb_api_key,$tmdb_base;
    $r=tmdbGet("$tmdb_base/movie/popular?api_key=$tmdb_api_key&language=$lang&page=$page");
    return $r['results']??[];
}
// Séries populaires
function tmdbTV($page=1,$lang='fr-FR'){
    global $tmdb_api_key,$tmdb_base;
    $r=tmdbGet("$tmdb_base/tv/popular?api_key=$tmdb_api_key&language=$lang&page=$page");
    return $r['results']??[];
}
// Tendances semaine (films+series)
function tmdbTrending($type='all',$lang='fr-FR'){
    global $tmdb_api_key,$tmdb_base;
    $r=tmdbGet("$tmdb_base/trending/$type/week?api_key=$tmdb_api_key&language=$lang");
    return $r['results']??[];
}
// Recherche multi (films+series)
function tmdbSearch($q,$lang='fr-FR'){
    global $tmdb_api_key,$tmdb_base;
    $r=tmdbGet("$tmdb_base/search/multi?api_key=$tmdb_api_key&language=$lang&query=".urlencode($q)."&include_adult=false");
    return array_values(array_filter($r['results']??[],fn($m)=>in_array($m['media_type']??'',['movie','tv'])));
}
// Detail film
function tmdbMovieDetail($id,$lang='fr-FR'){
    global $tmdb_api_key,$tmdb_base;
    return tmdbGet("$tmdb_base/movie/$id?api_key=$tmdb_api_key&language=$lang&append_to_response=credits,videos,similar");
}
// Detail série
function tmdbTVDetail($id,$lang='fr-FR'){
    global $tmdb_api_key,$tmdb_base;
    return tmdbGet("$tmdb_base/tv/$id?api_key=$tmdb_api_key&language=$lang&append_to_response=credits,videos,similar");
}
// Par genre
function tmdbDiscover($genreId,$type='movie',$lang='fr-FR',$page=1){
    global $tmdb_api_key,$tmdb_base;
    $r=tmdbGet("$tmdb_base/discover/$type?api_key=$tmdb_api_key&language=$lang&with_genres=$genreId&sort_by=popularity.desc&page=$page");
    return $r['results']??[];
}
// Genre IDs
function tmdbGenreId($slug,$type='movie'){
    $m=['action'=>28,'comedie'=>35,'horreur'=>27,'romance'=>10749,'drame'=>18,
        'enfants'=>16,'thriller'=>53,'scifi'=>878,'animation'=>16,'crime'=>80,
        'aventure'=>12,'fantasy'=>14,'disney'=>10751,'documentaire'=>99,'histoire'=>36];
    $tv=['action'=>10759,'comedie'=>35,'drame'=>18,'scifi'=>10765,'crime'=>80,
         'animation'=>16,'documentaire'=>99,'reality'=>10764];
    return ($type==='tv'?$tv:$m)[$slug]??28;
}
// Poster URL
function tmdbPoster($path,$size='w300'){
    return $path?"https://image.tmdb.org/t/p/{$size}{$path}":null;
}
// Trouver par ID externe (ex: IMDb tt...)
function tmdbFindById($extId, $source='imdb_id'){
    global $tmdb_api_key,$tmdb_base;
    $r=tmdbGet("$tmdb_base/find/$extId?api_key=$tmdb_api_key&external_source=$source&language=fr-FR");
    if(!empty($r['movie_results'])) return array_merge($r['movie_results'][0],['media_type'=>'movie']);
    if(!empty($r['tv_results'])) return array_merge($r['tv_results'][0],['media_type'=>'tv']);
    return null;
}
// Titre unifié (movie ou tv)
function tmdbTitle($m){return $m['title']??$m['name']??$m['original_title']??$m['original_name']??'Sans titre';}
// Année unifiée
function tmdbYear($m){return substr($m['release_date']??$m['first_air_date']??'',0,4);}
// Résumé pour chatbot
function tmdbSummary($m){
    $title=tmdbTitle($m);$year=tmdbYear($m);
    $rating=round($m['vote_average']??0,1);
    $overview=$m['overview']??'';
    $director='';
    foreach($m['credits']['crew']??[] as $c){if($c['job']==='Director'){$director=$c['name'];break;}}
    if(!$director&&!empty($m['created_by'])) $director=$m['created_by'][0]['name']??'';
    $cast=[];foreach(array_slice($m['credits']['cast']??[],0,4) as $a) $cast[]=$a['name'];
    $trailer='';
    foreach($m['videos']['results']??[] as $v){
        if($v['type']==='Trailer'&&$v['site']==='YouTube'){$trailer='https://youtu.be/'.$v['key'];break;}
    }
    $p=["$title ($year)","Note: $rating/10"];
    if($director) $p[]="Réalisateur/Créateur: $director";
    if($cast) $p[]="Avec: ".implode(', ',$cast);
    if($overview) $p[]=$overview;
    if($trailer) $p[]="Trailer: $trailer";
    return implode(' | ',$p);
}
