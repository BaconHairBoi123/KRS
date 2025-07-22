import { SidebarTrigger } from "@/components/ui/sidebar"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Clock, MapPin, User } from "lucide-react"

const scheduleData = [
  {
    day: "Senin",
    courses: [
      {
        time: "08:00 - 10:30",
        code: "CS101",
        name: "Algoritma dan Pemrograman",
        lecturer: "Dr. Ahmad Wijaya",
        room: "Lab Komputer 1",
        color: "bg-blue-100 border-blue-300 text-blue-800",
      },
      {
        time: "13:00 - 15:30",
        code: "MTK201",
        name: "Statistika",
        lecturer: "Prof. Maria Sari",
        room: "Ruang 205",
        color: "bg-green-100 border-green-300 text-green-800",
      },
    ],
  },
  {
    day: "Selasa",
    courses: [
      {
        time: "08:00 - 11:30",
        code: "MTK101",
        name: "Kalkulus I",
        lecturer: "Dr. Budi Santoso",
        room: "Ruang 201",
        color: "bg-purple-100 border-purple-300 text-purple-800",
      },
    ],
  },
  {
    day: "Rabu",
    courses: [
      {
        time: "10:30 - 13:00",
        code: "CS201",
        name: "Struktur Data",
        lecturer: "Prof. Siti Nurhaliza",
        room: "Lab Komputer 2",
        color: "bg-orange-100 border-orange-300 text-orange-800",
      },
    ],
  },
  {
    day: "Kamis",
    courses: [
      {
        time: "13:00 - 15:30",
        code: "CS301",
        name: "Basis Data",
        lecturer: "Dr. Lisa Permata",
        room: "Lab Database",
        color: "bg-red-100 border-red-300 text-red-800",
      },
    ],
  },
  {
    day: "Jumat",
    courses: [
      {
        time: "08:00 - 10:30",
        code: "CS302",
        name: "Rekayasa Perangkat Lunak",
        lecturer: "Prof. Andi Kurniawan",
        room: "Ruang 301",
        color: "bg-indigo-100 border-indigo-300 text-indigo-800",
      },
    ],
  },
  {
    day: "Sabtu",
    courses: [],
  },
  {
    day: "Minggu",
    courses: [],
  },
]

export default function JadwalPage() {
  return (
    <div className="flex flex-col">
      {/* Header */}
      <header className="flex h-16 shrink-0 items-center gap-2 border-b bg-white px-4">
        <SidebarTrigger className="-ml-1" />
        <div className="flex-1">
          <h1 className="text-xl font-semibold">Jadwal Kuliah</h1>
          <p className="text-sm text-gray-500">Semester Genap 2023/2024</p>
        </div>
      </header>

      <div className="flex-1 p-6">
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {scheduleData.map((day) => (
            <Card key={day.day} className="h-fit">
              <CardHeader className="pb-3">
                <CardTitle className="text-lg">{day.day}</CardTitle>
                <CardDescription>{day.courses.length} mata kuliah</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                {day.courses.length === 0 ? (
                  <div className="text-center py-8 text-gray-500">
                    <p>Tidak ada jadwal</p>
                  </div>
                ) : (
                  day.courses.map((course, index) => (
                    <div key={index} className={`p-3 rounded-lg border-2 ${course.color}`}>
                      <div className="flex items-center gap-2 mb-2">
                        <Badge variant="secondary" className="text-xs">
                          {course.code}
                        </Badge>
                        <div className="flex items-center gap-1 text-xs text-gray-600">
                          <Clock className="h-3 w-3" />
                          {course.time}
                        </div>
                      </div>

                      <h4 className="font-medium text-sm mb-2 leading-tight">{course.name}</h4>

                      <div className="space-y-1">
                        <div className="flex items-center gap-1 text-xs text-gray-600">
                          <User className="h-3 w-3" />
                          {course.lecturer}
                        </div>
                        <div className="flex items-center gap-1 text-xs text-gray-600">
                          <MapPin className="h-3 w-3" />
                          {course.room}
                        </div>
                      </div>
                    </div>
                  ))
                )}
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Summary */}
        <Card className="mt-6">
          <CardHeader>
            <CardTitle>Ringkasan Jadwal</CardTitle>
            <CardDescription>Informasi umum tentang jadwal kuliah Anda</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 md:grid-cols-3">
              <div className="text-center p-4 bg-blue-50 rounded-lg">
                <div className="text-2xl font-bold text-blue-600">
                  {scheduleData.reduce((total, day) => total + day.courses.length, 0)}
                </div>
                <div className="text-sm text-gray-600">Total Mata Kuliah</div>
              </div>
              <div className="text-center p-4 bg-green-50 rounded-lg">
                <div className="text-2xl font-bold text-green-600">21</div>
                <div className="text-sm text-gray-600">Total SKS</div>
              </div>
              <div className="text-center p-4 bg-purple-50 rounded-lg">
                <div className="text-2xl font-bold text-purple-600">5</div>
                <div className="text-sm text-gray-600">Hari Aktif</div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
