<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 50)->unique()->comment('工单编号');
            $table->foreignId('resident_id')->constrained();
            $table->foreignId('lease_id')->nullable()->constrained();
            $table->tinyInteger('type')->comment('工单类型:1普通维修2紧急维修');
            $table->tinyInteger('category')->comment('维修类别:1水电2门窗3墙体4家电5其他');
            $table->string('title', 200)->comment('报修标题');
            $table->text('description')->comment('报修描述');
            $table->string('contact_person', 50)->comment('联系人');
            $table->string('contact_phone', 20)->comment('联系电话');
            $table->dateTime('appointment_time')->nullable()->comment('预约时间');
            $table->dateTime('actual_start_time')->nullable()->comment('实际开始时间');
            $table->dateTime('actual_end_time')->nullable()->comment('实际结束时间');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->comment('指派维修人员');
            $table->foreignId('team_id')->nullable()->comment('维修班组');
            $table->decimal('material_cost', 12, 2)->default(0)->comment('材料费用');
            $table->decimal('labor_cost', 12, 2)->default(0)->comment('人工费用');
            $table->decimal('total_cost', 12, 2)->default(0)->comment('总费用');
            $table->tinyInteger('status')->default(1)->comment('状态:1待接单2已接单3维修中4待验收5已完成6已取消');
            $table->tinyInteger('urgency_level')->default(1)->comment('紧急程度:1一般2紧急3非常紧急');
            $table->text('repair_result')->nullable()->comment('维修结果');
            $table->text('cancel_reason')->nullable()->comment('取消原因');
            $table->boolean('has_photos')->default(false)->comment('是否有完工照片');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resident_id', 'status']);
            $table->index(['status', 'type']);
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('maintenance_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_order_id')->constrained()->onDelete('cascade');
            $table->string('photo_type', 20)->default('completion')->comment('照片类型:before repair after completion');
            $table->string('file_path', 500)->comment('文件路径');
            $table->string('file_name', 200)->comment('文件名');
            $table->string('file_size', 20)->nullable()->comment('文件大小');
            $table->text('description')->nullable()->comment('照片说明');
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('maintenance_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_order_id')->constrained()->onDelete('cascade');
            $table->string('material_name', 100)->comment('材料名称');
            $table->string('specification', 100)->nullable()->comment('规格型号');
            $table->decimal('quantity', 10, 2)->comment('数量');
            $table->string('unit', 10)->comment('单位');
            $table->decimal('unit_price', 12, 2)->comment('单价');
            $table->decimal('amount', 12, 2)->comment('金额');
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_order_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('action_type')->comment('操作类型:1派单2接单3开始维修4完工5验收6取消');
            $table->text('action_content')->comment('操作内容');
            $table->foreignId('operator_id')->nullable()->constrained('users');
            $table->string('operator_name', 50)->comment('操作人');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_timelines');
        Schema::dropIfExists('maintenance_materials');
        Schema::dropIfExists('maintenance_photos');
        Schema::dropIfExists('maintenance_orders');
    }
};
