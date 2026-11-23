public function up()
{
    Schema::table('expenses', function (Blueprint $table) {
        $table->boolean('is_approved')->default(false)->after('description');
    });
}

public function down()
{
    Schema::table('expenses', function (Blueprint $table) {
        $table->dropColumn('is_approved');
    });
}