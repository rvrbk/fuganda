<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Seed the application's location reference data with all official Ugandan districts.
     * 
     * Uganda has 135+ districts grouped into 4 regions (Central, Eastern, Northern, Western).
     * This seeder includes all districts with their primary urban centers as cities.
     */
    public function run(): void
    {
        $locations = [
            // Central Region - Buganda South
            ['country' => 'Uganda', 'district' => 'Buikwe', 'city' => 'Buikwe', 'slug' => 'uganda-buikwe-buikwe', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Bukomansimbi', 'city' => 'Bukomansimbi', 'slug' => 'uganda-bukomansimbi-bukomansimbi', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Butambala', 'city' => 'Butambala', 'slug' => 'uganda-butambala-butambala', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Buvuma', 'city' => 'Buvuma', 'slug' => 'uganda-buvuma-buvuma', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Gomba', 'city' => 'Gomba', 'slug' => 'uganda-gomba-gomba', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kalangala', 'city' => 'Kalangala', 'slug' => 'uganda-kalangala-kalangala', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kalungu', 'city' => 'Kalungu', 'slug' => 'uganda-kalungu-kalungu', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kasanda', 'city' => 'Kasanda', 'slug' => 'uganda-kasanda-kasanda', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kyotera', 'city' => 'Kyotera', 'slug' => 'uganda-kyotera-kyotera', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Lwengo', 'city' => 'Lwengo', 'slug' => 'uganda-lwengo-lwengo', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Lyantonde', 'city' => 'Lyantonde', 'slug' => 'uganda-lyantonde-lyantonde', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Masaka', 'city' => 'Masaka', 'slug' => 'uganda-masaka-masaka', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Masaka City', 'city' => 'Masaka City', 'slug' => 'uganda-masaka-city-masaka-city', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Mpigi', 'city' => 'Mpigi', 'slug' => 'uganda-mpigi-mpigi', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Rakai', 'city' => 'Rakai', 'slug' => 'uganda-rakai-rakai', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Sembabule', 'city' => 'Sembabule', 'slug' => 'uganda-sembabule-sembabule', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Wakiso', 'city' => 'Wakiso', 'slug' => 'uganda-wakiso-wakiso', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Wakiso', 'city' => 'Entebbe', 'slug' => 'uganda-wakiso-entebbe', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Wakiso', 'city' => 'Nansana', 'slug' => 'uganda-wakiso-nansana', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Wakiso', 'city' => 'Kira', 'slug' => 'uganda-wakiso-kira', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Wakiso', 'city' => 'Ssabagabo', 'slug' => 'uganda-wakiso-ssabagabo', 'is_active' => true],
            
            // Central Region - Buganda North
            ['country' => 'Uganda', 'district' => 'Kayunga', 'city' => 'Kayunga', 'slug' => 'uganda-kayunga-kayunga', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kiboga', 'city' => 'Kiboga', 'slug' => 'uganda-kiboga-kiboga', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kyankwanzi', 'city' => 'Kyankwanzi', 'slug' => 'uganda-kyankwanzi-kyankwanzi', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Luwero', 'city' => 'Luwero', 'slug' => 'uganda-luwero-luwero', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Mityana', 'city' => 'Mityana', 'slug' => 'uganda-mityana-mityana', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Mubende', 'city' => 'Mubende', 'slug' => 'uganda-mubende-mubende', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Mukono', 'city' => 'Mukono', 'slug' => 'uganda-mukono-mukono', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Mukono', 'city' => 'Seeta', 'slug' => 'uganda-mukono-seeta', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Mukono', 'city' => 'Njeru', 'slug' => 'uganda-mukono-njeru', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Nakaseke', 'city' => 'Nakaseke', 'slug' => 'uganda-nakaseke-nakaseke', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Nakasongola', 'city' => 'Nakasongola', 'slug' => 'uganda-nakasongola-nakasongola', 'is_active' => true],
            
            // Central Region - Kampala Capital City
            ['country' => 'Uganda', 'district' => 'Kampala', 'city' => 'Kampala', 'slug' => 'uganda-kampala-kampala', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kampala', 'city' => 'Kampala Central', 'slug' => 'uganda-kampala-kampala-central', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kampala', 'city' => 'Kawempe', 'slug' => 'uganda-kampala-kawempe', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kampala', 'city' => 'Makindye', 'slug' => 'uganda-kampala-makindye', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kampala', 'city' => 'Nakawa', 'slug' => 'uganda-kampala-nakawa', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kampala', 'city' => 'Rubaga', 'slug' => 'uganda-kampala-rubaga', 'is_active' => true],
            
            // Central Region - Busoga
            ['country' => 'Uganda', 'district' => 'Bugiri', 'city' => 'Bugiri', 'slug' => 'uganda-bugiri-bugiri', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Bugweri', 'city' => 'Bugweri', 'slug' => 'uganda-bugweri-bugweri', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Buyende', 'city' => 'Buyende', 'slug' => 'uganda-buyende-buyende', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Iganga', 'city' => 'Iganga', 'slug' => 'uganda-iganga-iganga', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Jinja', 'city' => 'Jinja', 'slug' => 'uganda-jinja-jinja', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Jinja', 'city' => 'Buwenge', 'slug' => 'uganda-jinja-buwenge', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Jinja City', 'city' => 'Jinja City', 'slug' => 'uganda-jinja-city-jinja-city', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kaliro', 'city' => 'Kaliro', 'slug' => 'uganda-kaliro-kaliro', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kamuli', 'city' => 'Kamuli', 'slug' => 'uganda-kamuli-kamuli', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Luuka', 'city' => 'Luuka', 'slug' => 'uganda-luuka-luuka', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Mayuge', 'city' => 'Mayuge', 'slug' => 'uganda-mayuge-mayuge', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Namayingo', 'city' => 'Namayingo', 'slug' => 'uganda-namayingo-namayingo', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Namutumba', 'city' => 'Namutumba', 'slug' => 'uganda-namutumba-namutumba', 'is_active' => true],
            
            // Western Region - Bunyoro
            ['country' => 'Uganda', 'district' => 'Buliisa', 'city' => 'Buliisa', 'slug' => 'uganda-buliisa-buliisa', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Hoima', 'city' => 'Hoima', 'slug' => 'uganda-hoima-hoima', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kagadi', 'city' => 'Kagadi', 'slug' => 'uganda-kagadi-kagadi', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kakumiro', 'city' => 'Kakumiro', 'slug' => 'uganda-kakumiro-kakumiro', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kibaale', 'city' => 'Kibaale', 'slug' => 'uganda-kibaale-kibaale', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kiryandongo', 'city' => 'Kiryandongo', 'slug' => 'uganda-kiryandongo-kiryandongo', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Masindi', 'city' => 'Masindi', 'slug' => 'uganda-masindi-masindi', 'is_active' => true],
            
            // Western Region - Toro
            ['country' => 'Uganda', 'district' => 'Kabarole', 'city' => 'Kabarole', 'slug' => 'uganda-kabarole-kabarole', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kabarole', 'city' => 'Fort Portal City', 'slug' => 'uganda-kabarole-fort-portal-city', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kamwenge', 'city' => 'Kamwenge', 'slug' => 'uganda-kamwenge-kamwenge', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kyegegwa', 'city' => 'Kyegegwa', 'slug' => 'uganda-kyegegwa-kyegegwa', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kyenjojo', 'city' => 'Kyenjojo', 'slug' => 'uganda-kyenjojo-kyenjojo', 'is_active' => true],
            
            // Western Region - Ankole
            ['country' => 'Uganda', 'district' => 'Bushenyi', 'city' => 'Bushenyi', 'slug' => 'uganda-bushenyi-bushenyi', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Ibanda', 'city' => 'Ibanda', 'slug' => 'uganda-ibanda-ibanda', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Isingiro', 'city' => 'Isingiro', 'slug' => 'uganda-isingiro-isingiro', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Mbarara', 'city' => 'Mbarara', 'slug' => 'uganda-mbarara-mbarara', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Mbarara City', 'city' => 'Mbarara City', 'slug' => 'uganda-mbarara-city-mbarara-city', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Ntungamo', 'city' => 'Ntungamo', 'slug' => 'uganda-ntungamo-ntungamo', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Rubirizi', 'city' => 'Rubirizi', 'slug' => 'uganda-rubirizi-rubirizi', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Sheema', 'city' => 'Sheema', 'slug' => 'uganda-sheema-sheema', 'is_active' => true],
            
            // Western Region - Kigezi
            ['country' => 'Uganda', 'district' => 'Kabale', 'city' => 'Kabale', 'slug' => 'uganda-kabale-kabale', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kanungu', 'city' => 'Kanungu', 'slug' => 'uganda-kanungu-kanungu', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kisoro', 'city' => 'Kisoro', 'slug' => 'uganda-kisoro-kisoro', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Rukiga', 'city' => 'Rukiga', 'slug' => 'uganda-rukiga-rukiga', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Rukungiri', 'city' => 'Rukungiri', 'slug' => 'uganda-rukungiri-rukungiri', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Rubanda', 'city' => 'Rubanda', 'slug' => 'uganda-rubanda-rubanda', 'is_active' => true],
            
            // Western Region - Rwenzori
            ['country' => 'Uganda', 'district' => 'Bundibugyo', 'city' => 'Bundibugyo', 'slug' => 'uganda-bundibugyo-bundibugyo', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kasese', 'city' => 'Kasese', 'slug' => 'uganda-kasese-kasese', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Ntoroko', 'city' => 'Ntoroko', 'slug' => 'uganda-ntoroko-ntoroko', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Bunyangabu', 'city' => 'Bunyangabu', 'slug' => 'uganda-bunyangabu-bunyangabu', 'is_active' => true],
            
            // Western Region - Additional
            ['country' => 'Uganda', 'district' => 'Kikuube', 'city' => 'Kikuube', 'slug' => 'uganda-kikuube-kikuube', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kitagwenda', 'city' => 'Kitagwenda', 'slug' => 'uganda-kitagwenda-kitagwenda', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kazo', 'city' => 'Kazo', 'slug' => 'uganda-kazo-kazo', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kiruhura', 'city' => 'Kiruhura', 'slug' => 'uganda-kiruhura-kiruhura', 'is_active' => true],
            
            // Eastern Region - Bukedi
            ['country' => 'Uganda', 'district' => 'Budaka', 'city' => 'Budaka', 'slug' => 'uganda-budaka-budaka', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Busia', 'city' => 'Busia', 'slug' => 'uganda-busia-busia', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Butaleja', 'city' => 'Butaleja', 'slug' => 'uganda-butaleja-butaleja', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Butebo', 'city' => 'Butebo', 'slug' => 'uganda-butebo-butebo', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kibuku', 'city' => 'Kibuku', 'slug' => 'uganda-kibuku-kibuku', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Pallisa', 'city' => 'Pallisa', 'slug' => 'uganda-pallisa-pallisa', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Tororo', 'city' => 'Tororo', 'slug' => 'uganda-tororo-tororo', 'is_active' => true],
            
            // Eastern Region - Bugisu
            ['country' => 'Uganda', 'district' => 'Bulambuli', 'city' => 'Bulambuli', 'slug' => 'uganda-bulambuli-bulambuli', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Bududa', 'city' => 'Bududa', 'slug' => 'uganda-bududa-bududa', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Mbale', 'city' => 'Mbale', 'slug' => 'uganda-mbale-mbale', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Mbale City', 'city' => 'Mbale City', 'slug' => 'uganda-mbale-city-mbale-city', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Manafwa', 'city' => 'Manafwa', 'slug' => 'uganda-manafwa-manafwa', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Namisindwa', 'city' => 'Namisindwa', 'slug' => 'uganda-namisindwa-namisindwa', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Sironko', 'city' => 'Sironko', 'slug' => 'uganda-sironko-sironko', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kween', 'city' => 'Kween', 'slug' => 'uganda-kween-kween', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kapchorwa', 'city' => 'Kapchorwa', 'slug' => 'uganda-kapchorwa-kapchorwa', 'is_active' => true],
            
            // Eastern Region - Teso
            ['country' => 'Uganda', 'district' => 'Amuria', 'city' => 'Amuria', 'slug' => 'uganda-amuria-amuria', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kaberamaido', 'city' => 'Kaberamaido', 'slug' => 'uganda-kaberamaido-kaberamaido', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Katakwi', 'city' => 'Katakwi', 'slug' => 'uganda-katakwi-katakwi', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kumi', 'city' => 'Kumi', 'slug' => 'uganda-kumi-kumi', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Ngora', 'city' => 'Ngora', 'slug' => 'uganda-ngora-ngora', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Serere', 'city' => 'Serere', 'slug' => 'uganda-serere-serere', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Soroti', 'city' => 'Soroti', 'slug' => 'uganda-soroti-soroti', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Soroti City', 'city' => 'Soroti City', 'slug' => 'uganda-soroti-city-soroti-city', 'is_active' => true],
            
            // Eastern Region - Karamoja
            ['country' => 'Uganda', 'district' => 'Abim', 'city' => 'Abim', 'slug' => 'uganda-abim-abim', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Amudat', 'city' => 'Amudat', 'slug' => 'uganda-amudat-amudat', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kaabong', 'city' => 'Kaabong', 'slug' => 'uganda-kaabong-kaabong', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Karenga', 'city' => 'Karenga', 'slug' => 'uganda-karenga-karenga', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kotido', 'city' => 'Kotido', 'slug' => 'uganda-kotido-kotido', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Moroto', 'city' => 'Moroto', 'slug' => 'uganda-moroto-moroto', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Napak', 'city' => 'Napak', 'slug' => 'uganda-napak-napak', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Nakapiripirit', 'city' => 'Nakapiripirit', 'slug' => 'uganda-nakapiripirit-nakapiripirit', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Nabilatuk', 'city' => 'Nabilatuk', 'slug' => 'uganda-nabilatuk-nabilatuk', 'is_active' => true],
            
            // Northern Region - Acholi
            ['country' => 'Uganda', 'district' => 'Agago', 'city' => 'Agago', 'slug' => 'uganda-agago-agago', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Amuru', 'city' => 'Amuru', 'slug' => 'uganda-amuru-amuru', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Gulu', 'city' => 'Gulu', 'slug' => 'uganda-gulu-gulu', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Gulu City', 'city' => 'Gulu City', 'slug' => 'uganda-gulu-city-gulu-city', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kitgum', 'city' => 'Kitgum', 'slug' => 'uganda-kitgum-kitgum', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Lamwo', 'city' => 'Lamwo', 'slug' => 'uganda-lamwo-lamwo', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Nwoya', 'city' => 'Nwoya', 'slug' => 'uganda-nwoya-nwoya', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Omoro', 'city' => 'Omoro', 'slug' => 'uganda-omoro-omoro', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Pader', 'city' => 'Pader', 'slug' => 'uganda-pader-pader', 'is_active' => true],
            
            // Northern Region - Lango
            ['country' => 'Uganda', 'district' => 'Alebtong', 'city' => 'Alebtong', 'slug' => 'uganda-alebtong-alebtong', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Apac', 'city' => 'Apac', 'slug' => 'uganda-apac-apac', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Dokolo', 'city' => 'Dokolo', 'slug' => 'uganda-dokolo-dokolo', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kwania', 'city' => 'Kwania', 'slug' => 'uganda-kwania-kwania', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Lira', 'city' => 'Lira', 'slug' => 'uganda-lira-lira', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Lira City', 'city' => 'Lira City', 'slug' => 'uganda-lira-city-lira-city', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Otuke', 'city' => 'Otuke', 'slug' => 'uganda-otuke-otuke', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Oyam', 'city' => 'Oyam', 'slug' => 'uganda-oyam-oyam', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kole', 'city' => 'Kole', 'slug' => 'uganda-kole-kole', 'is_active' => true],
            
            // Northern Region - West Nile
            ['country' => 'Uganda', 'district' => 'Arua', 'city' => 'Arua', 'slug' => 'uganda-arua-arua', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Arua City', 'city' => 'Arua City', 'slug' => 'uganda-arua-city-arua-city', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Koboko', 'city' => 'Koboko', 'slug' => 'uganda-koboko-koboko', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Maracha', 'city' => 'Maracha', 'slug' => 'uganda-maracha-maracha', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Moyo', 'city' => 'Moyo', 'slug' => 'uganda-moyo-moyo', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Nebbi', 'city' => 'Nebbi', 'slug' => 'uganda-nebbi-nebbi', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Obongi', 'city' => 'Obongi', 'slug' => 'uganda-obongi-obongi', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Pakwach', 'city' => 'Pakwach', 'slug' => 'uganda-pakwach-pakwach', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Terego', 'city' => 'Terego', 'slug' => 'uganda-terego-terego', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Yumbe', 'city' => 'Yumbe', 'slug' => 'uganda-yumbe-yumbe', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Zombo', 'city' => 'Zombo', 'slug' => 'uganda-zombo-zombo', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Madi-Okollo', 'city' => 'Madi-Okollo', 'slug' => 'uganda-madi-okollo-madi-okollo', 'is_active' => true],
            
            // Additional districts from official list
            ['country' => 'Uganda', 'district' => 'Adjumani', 'city' => 'Adjumani', 'slug' => 'uganda-adjumani-adjumani', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Amolatar', 'city' => 'Amolatar', 'slug' => 'uganda-amolatar-amolatar', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Buhweju', 'city' => 'Buhweju', 'slug' => 'uganda-buhweju-buhweju', 'is_active' => true],
            ['country' => 'Uganda', 'district' => 'Kapelebyong', 'city' => 'Kapelebyong', 'slug' => 'uganda-kapelebyong-kapelebyong', 'is_active' => true],
        ];

        foreach ($locations as $location) {
            Location::query()->updateOrCreate(
                ['slug' => $location['slug']],
                $location
            );
        }
    }
}
