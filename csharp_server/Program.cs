using Microsoft.AspNetCore.Builder;
using Microsoft.AspNetCore.Http;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Hosting;
using Microsoft.AspNetCore.Mvc;
using System.IO;
using System.Text;
using System.Threading.Tasks;

var builder = WebApplication.CreateBuilder(args);

// Registrar el servicio de validación de firma.
builder.Services.AddSingleton<ISignatureValidationService, SignatureValidationService>();

var app = builder.Build();

app.MapPost("/signature", async (HttpRequest request, [FromServices] ISignatureValidationService validationService) =>
{
    // 1. Extraer los componentes de la notificación
    if (!request.Headers.TryGetValue("X-Signature", out var signature))
    {
        return Results.BadRequest("Cabecera X-Signature es requerida");
    }

    if (!request.Headers.TryGetValue("X-Signature-Nonce", out var nonce))
    {
        return Results.BadRequest("Cabecera X-Signature-Nonce es requerida");
    }

    // Leer el cuerpo crudo de la solicitud
    string payload;
    using (var reader = new StreamReader(request.Body, Encoding.UTF8))
    {
        payload = await reader.ReadToEndAsync();
    }
    
    // 2. Validar la firma
    try
    {
        await validationService.ValidateSyPagoSignatureAsync(signature.First()!, payload, nonce.First()!);
    }
    catch (System.Exception ex)
    {
        // La firma no es válida.
        System.Console.WriteLine($"Error al validar la firma: {ex.Message}");
        return Results.Unauthorized();
    }

    // 3. La firma es válida.
    System.Console.WriteLine("Firma válida. Procesando notificación...");
    
    return Results.Ok(new { message = "Firma válida y notificación recibida C#" });
});

// Ejecutar la aplicación en el puerto 5900
app.Run("http://localhost:5900"); 