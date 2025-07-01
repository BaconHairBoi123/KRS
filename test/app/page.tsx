import { SidebarTrigger } from "@/components/ui/sidebar"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { StatsCard } from "@/components/stats-card"
import { BookOpen, Calendar, GraduationCap, Clock, Bell, FileText, Users, TrendingUp } from "lucide-react"

export default function Dashboard() {
  const announcements = [
    {
      title: "Batas Akhir Pengisian KRS",
      description: "Pengisian KRS semester genap berakhir pada 15 Januari 2024",
      date: "2 hari lagi",
      urgent: true,
    },
    {
      title: "Pembayaran UKT Semester Genap",
      description: "Jangan lupa melakukan pembayaran UKT sebelum batas waktu",
      date: "5 hari lagi",
      urgent: false,
    },
    {
      title: "Jadwal Ujian Tengah Semester",
      description: "Jadwal UTS telah dirilis, silakan cek di menu jadwal",
      date: "1 minggu lagi",
      urgent: false,
    },
  ]

  const quickActions = [
    { title: "Isi KRS", icon: BookOpen, href: "/krs", color: "bg-blue-600" },
    { title: "Lihat Jadwal", icon: Calendar, href: "/jadwal", color: "bg-green-600" },
    { title: "Transkrip", icon: FileText, href: "/transkrip", color: "bg-purple-600" },
    { title: "Profil", icon: Users, href: "/profil", color: "bg-orange-600" },
  ]

  return (
    <div className="flex flex-col">
      {/* Header */}
      <header className="flex h-16 shrink-0 items-center gap-2 border-b bg-white px-4">
        <SidebarTrigger className="-ml-1" />
        <div className="flex-1">
          <h1 className="text-xl font-semibold">Dashboard KRS</h1>
          <p className="text-sm text-gray-500">Selamat datang, John Doe</p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm">
            <Bell className="h-4 w-4 mr-2" />
            Notifikasi
          </Button>
        </div>
      </header>

      {/* Main Content */}
      <div className="flex-1 p-6 space-y-6">
        {/* Stats Cards */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <StatsCard
            title="Total SKS Diambil"
            value="21"
            change="SKS"
            changeType="neutral"
            icon={BookOpen}
            iconColor="bg-blue-600"
          />
          <StatsCard
            title="Mata Kuliah Terdaftar"
            value="7"
            change="MK"
            changeType="neutral"
            icon={Calendar}
            iconColor="bg-green-600"
          />
          <StatsCard
            title="IPK Sementara"
            value="3.45"
            change="+0.12"
            changeType="positive"
            icon={GraduationCap}
            iconColor="bg-purple-600"
          />
          <StatsCard
            title="Semester Aktif"
            value="5"
            change="Semester"
            changeType="neutral"
            icon={Clock}
            iconColor="bg-orange-600"
          />
        </div>

        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {/* Quick Actions */}
          <Card>
            <CardHeader>
              <CardTitle>Aksi Cepat</CardTitle>
              <CardDescription>Akses fitur utama dengan cepat</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 gap-3">
                {quickActions.map((action) => (
                  <Button key={action.title} variant="outline" className="h-20 flex-col gap-2" asChild>
                    <a href={action.href}>
                      <div className={`p-2 rounded-lg ${action.color}`}>
                        <action.icon className="h-4 w-4 text-white" />
                      </div>
                      <span className="text-xs">{action.title}</span>
                    </a>
                  </Button>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Announcements */}
          <Card className="md:col-span-2">
            <CardHeader>
              <CardTitle>Pengumuman Akademik</CardTitle>
              <CardDescription>Informasi terbaru seputar kegiatan akademik</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {announcements.map((announcement, index) => (
                  <div
                    key={index}
                    className={`p-4 rounded-lg border-l-4 ${
                      announcement.urgent ? "border-red-500 bg-red-50" : "border-blue-500 bg-blue-50"
                    }`}
                  >
                    <div className="flex justify-between items-start">
                      <div className="flex-1">
                        <h4 className="font-medium text-gray-900">{announcement.title}</h4>
                        <p className="text-sm text-gray-600 mt-1">{announcement.description}</p>
                      </div>
                      <span
                        className={`text-xs px-2 py-1 rounded-full ${
                          announcement.urgent ? "bg-red-100 text-red-800" : "bg-blue-100 text-blue-800"
                        }`}
                      >
                        {announcement.date}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Academic Progress */}
        <Card>
          <CardHeader>
            <CardTitle>Progress Akademik</CardTitle>
            <CardDescription>Perkembangan IPK per semester</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
              <div className="text-center">
                <TrendingUp className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                <p className="text-gray-500">Grafik IPK akan ditampilkan di sini</p>
                <p className="text-sm text-gray-400 mt-2">Integrasi dengan sistem nilai diperlukan</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
